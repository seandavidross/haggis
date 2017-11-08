<?php

function playCombo($cards_ids, $combo_id)
{
  self::checkAction('playCombo');
  $player_id = self::getActivePlayerId();
  
  if (count($cards_ids) == 0) 
      throw new feException(self::_('No cards selected'), true);
  
  $cards = $this->cards->getCards($cards_ids);
  
  $combos = self::analyzeCombo($cards);
  
  $currentComboType = self::getGameStateValue('combotype');
  $bOpeningCombination = ($currentComboType == 0);
  
  if (count($combos) == 0) 
  {
    throw new feException( self::_( "This is not a valid card combination. You"
                                  . " should play a set, a sequence, or a bomb"
                                  )
                         , true
                         );
  } 
  elseif (count($combos) > 1) 
  {
    // 		There is a choice between several combo
    if ($combo_id === null) 
    {
      // 			=> give player a choice
      self::notifyPlayer($player_id, 'multipleCombos', '', $combos);
      return;
    } 
    else 
    {
      if (!isset($combos[$combo_id])) 
          throw new feException("Wrong combo");
      
      $combo = $combos[$combo_id];
    }
  } 
  
  $combo_type_id = self::combo_type_to_id($combo['type']);
  
  if ($combo['type'] == 'bomb')
    self::incStat(1, 'bomb_number', $player_id);
  
  if (!$bOpeningCombination) 
  {
    $bTrickJustBombed = false;
    $current_combo_type = self::getGameStateValue('combotype');
    // This is not an opening combination => we must perform checks
    if ($combo_type_id != $current_combo_type) 
    {
      if ($combo['type'] != 'bomb') 
      {
        if ($current_combo_type == 1) 
        {
          throw new feException( self::_( "Wrong card combination type: you"
                                        . " should play a set or a bomb"
                                        )
                               , true
                               );
        } 
        elseif ($current_combo_type == 2) 
        {
          throw new feException( self::_( "Wrong card combination type: you"
                                        . " should play a sequence or a bomb"
                                        )
                               , true
                               );
        } 
        else 
        {
          throw new feException( self::_( "Wrong card combination type:"
                                        . " you should play a bomb"
                                        )
                               , true
                               );
        }
      } 
      else 
      {
        // A bomb can be played anytime. From now only bombs can be played.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  
        self::setGameStateValue('combotype', self::combo_type_to_id('bomb'));
        $bTrickJustBombed = true;
      }
    }

    if ( $combo['type'] != 'bomb' 
         && $combo['nbr'] != self::getGameStateValue('combonbr')) 
    {
      throw new feException( sprintf( self::_("You must play %s cards")
                                    , self::getGameStateValue('combonbr')
                                    )
                           , true
                           );
    }
    if ($combo['type'] == 'sequence') 
    {
      if ($combo['serienbr'] != self::getGameStateValue('comboserienbr')) 
      {
        throw new feException( sprintf( self::_("You must play %s sequences")
                                      , self::getGameStateValue('comboserienbr')
                                      )
                             , true
                             );
      }
    }
    if ($combo['value'] <= self::getGameStateValue('combovalue')) 
    {
      if (!$bTrickJustBombed) 
      {
        throw new feException( self::_( "You must play a higher combination " 
                                      . "than the previous one"
                                      )
                             , true
                             );
      }
    }
    
    self::setGameStateValue('combovalue', $combo['value']);
  } 
  else 
  {
    // 		First set
    self::setGameStateValue('combotype', $combo_type_id);
    self::setGameStateValue('combonbr', $combo['nbr']);
    self::setGameStateValue('comboserienbr', $combo['serienbr']);
    self::setGameStateValue('combovalue', $combo['value']);
  }
  
  self::setGameStateValue('nbrPass', 0);
  // 	number of consecutive pass action => reset
  $card_count = $this->cards->countCardsByLocationArgs('hand');
  $player_still_in_round = count($card_count);
  self::setGameStateValue('nbrPassToWin', $player_still_in_round-1);
  // 	number of consecutive pass action to win => reset

  // 	From this step, player manage to play a regular combination => put this combo on the table                                                                                                                                                
  $combo_display = implode(',', $combo['display']);
  self::DbQuery("INSERT INTO combo (combo_player_id, combo_display) " 
               . "VALUES ('$player_id', '$combo_display') "
               );

  $combo_no = self::DbGetLastId();
  
  $this->cards->moveCards($cards_ids, 'table', $combo_no);
  
  $cards_number = count($cards_ids);
  
  $combo_description_i18n = array('combination');
  $notification_description = clienttranslate('${player_name} plays a ${combination}');
  
  $notification_args = 
    array( 'i18n' => $combo_description_i18n
         , 'player_id' => $player_id
         , 'player_name' => self::getActivePlayerName()
         , 'cards' => $cards
         , 'display' => $combo['display']
         , 'combo_no' => $combo_no
         , 'combination' => $combo['type']
         , 'nbr' => $cards_number
         );
  
  if ($combo['type'] == 'sequence') 
  {
    if ($combo['serienbr'] > 1) 
    {
      $notification_description = 
        clienttranslate('${player_name} plays a sequence of ${ofakind} of a kind');

      $notification_args['combination'] = clienttranslate('sequences');
      $notification_args['ofakind'] = $cards_number / $combo['serienbr'];
    }
  }
  
  self::notifyAllPlayers( 'playCombo'
                        , $notification_description
                        , $notification_args
                        );
  
  self::setGameStateValue('lastComboPlayer', $player_id);
  
  if ($this->cards->countCardInLocation('hand', $player_id) == 0) 
  {
    // 		This player gets out of the trick
    
    // 		... scoring !
    
    // 		Count the number of cards in hand on opponent hands with the biggest number of cards
    $hand_count = $this->cards->countCardsByLocationArgs('hand');
    $opponent_with_biggest = getKeyWithMaximum($hand_count, true, false);
    $card_number = $hand_count[$opponent_with_biggest];
    $number_of_player_in_round = count($hand_count);
    $players = self::loadPlayersBasicInfos();
    
    $score = 5 * $card_number;
    
    self::notifyAllPlayers('playerGoOut'
                          
                          , clienttranslate( '${player_name} has shed the '
                                            . 'cards from his hand and gets '
                                            . '${score} points (5 x '
                                            . '${card_number} cards in the '
                                            . 'hand of ${opponent_player})'
                                            )
                          
                          , array( 'player_id' => 
                                      $player_id
                                  
                                  , 'player_name' => 
                                      self::getActivePlayerName()
                                  
                                  , 'score' => 
                                      $score
                              
                                  , 'card_number' => 
                                      $card_number
                          
                                  , 'opponent_player' => 
                                      $players[$opponent_with_biggest]['player_name']
                                  )
                          );

    $bFirstToGoOut = false;

    if ($number_of_player_in_round == 1 && count($players)==2)
        $bFirstToGoOut = true;
    
    if ($number_of_player_in_round == 2 && count($players)==3)
        $bFirstToGoOut = true;
    
    if ($bFirstToGoOut) 
    {
      self::setGameStateValue('lastRoundWinner', $player_id);
      self::resolveBets($player_id);
    }
    
    self::DbQuery("UPDATE player 
                   SET player_score=player_score+$score ,
                   player_points_remaining=player_points_remaining+$score
                   WHERE player_id='$player_id' "
                 );
  }
  
  $this->gamestate->nextState('');
}
