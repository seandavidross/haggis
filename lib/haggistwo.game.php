<?php
 /**
  * haggistwo.game.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  *
  *
  * haggistwo main game core
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class HaggisTwo extends Table
{
  function HaggisTwo( )
  {
    parent::__construct();
    self::initGameStateLabels( array(  "game_duration" => 100,
                                       "dealer" => 11, "lastTrickWinner" => 12, "lastRoundWinner" => 13,
                                       "combotype" => 14, "combonbr" => 15, "comboserienbr" => 16, "combovalue" => 17,
                                       "lastComboPlayer" => 18, "nbrPass" => 19, "nbrPassToWin" => 20 ) );

    $this->cards = self::getNew( "module.common.deck" );
    $this->cards->init( "card" );
  }

    function getGameName() {
        return "haggistwo";
    }

    protected function setupNewGame( $players, $options = array() )
    {
        $sql = "DELETE FROM player WHERE 1 ";
        self::DbQuery( $sql );

        // Create players
        $default_color = array( "ff0000", "008000", "0000ff", "ffa500" );
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_color );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reloadPlayersBasicInfos();

        // Create cards
        $this->cards->createCards( $this->card_initial );

        // 2 players mode: not all cards are used
        if( count( $players ) <= 2 )
        {
            // .. remove a serie
            $sql = "UPDATE card SET card_location='removed' WHERE card_type='4' ";
            self::DbQuery( $sql );

        }

        self::setGameStateInitialValue( 'dealer', 0 );
        self::setGameStateInitialValue( 'lastTrickWinner', 0 );
        self::setGameStateInitialValue( 'lastRoundWinner', 0 );
        self::setGameStateInitialValue( 'combotype', 0 );   // 0 = no combo / 1 = set / 2 = sequence / 3 = bomb
        self::setGameStateInitialValue( 'combonbr', 0 );         // number of cards of current combo
        self::setGameStateInitialValue( 'comboserienbr', 0 );    // number of series of current combo
        self::setGameStateInitialValue( 'combovalue', 0 );       // value of current combo
        self::setGameStateInitialValue( 'lastComboPlayer', 0 );   // player who played the last non void combo
        self::setGameStateInitialValue( 'nbrPass', 0 );   // number of consecutive pass
        self::setGameStateInitialValue( 'nbrPassToWin', 2 );   // number of consecutive pass needed to win the trick

        self::initStat( 'table', 'round_number', 0 );
        self::initStat( 'table', 'trick_number', 0 );
        self::initStat( 'table', 'trick_bombed', 0 );

        self::initStat( 'player', 'tricks_win', 0 );
        self::initStat( 'player', 'bomb_number', 0 );
        self::initStat( 'player', 'littlebet_number', 0 );
        self::initStat( 'player', 'bigbet_number', 0 );
        self::initStat( 'player', 'successfulbet_number', 0 );
    }

    // Get all datas (complete reset request from client side)
    protected function getAllDatas()
    {
        $result = array( 'players' => array() );

        // Add players haggistwo specific infos
        $sql = "SELECT player_id id, player_score score, player_bet, player_points_captured ";
        $sql .= "FROM player ";
        $sql .= "WHERE 1 ";
        $dbres = self::DbQuery( $sql );
        while( $player = mysql_fetch_assoc( $dbres ) )
        {
            $result['players'][ $player['id'] ] = $player;
        }

        // Cards in player hand
        global $g_user;
        $result['hand'] = $this->cards->getCardsInLocation( 'hand', $g_user->get_id() );

        // Check if there is a rainbow bomb / uniform bomb
        $result['bombcheck'] = self::checkBombsAmongCards( $result['hand'] );

        // Hands infos
        $result['handcount'] = $this->cards->countCardsByLocationArgs( 'hand' );

        // Return jack/queen/king for each player
        $result['wildcards'] = $this->cards->getCardsOfType( 'wild' );

        // Cards on table
        $result['table'] = $this->cards->getCardsInLocation( 'table' );

        // Player / combo association
        $sql = "SELECT combo_id id, combo_player_id player_id, combo_display display FROM combo ORDER BY combo_id";
        $result['combo'] = self::getCollectionFromDB( $sql );
        foreach( $result['combo'] as $combo_id => $combo )
        {
            $result['combo'][$combo_id]['display'] = explode( ',', $combo['display'] );
        }

        return $result;
    }

    // Return an array with options infos for this game
    function getGameOptionsInfos()
    {
        return array(
            10 => array(
                'name' => self::_('Game duration'),
                'values' => array(
                            2 => self::_('Long game (350 points)'),
                            1 => self::_('Short game (250 points)')
                        )
            )
        );
    }

    function getGameProgression()
    {
        // Game progression: get player maximum score
        $max_score = self::getUniqueValueFromDB( "SELECT MAX(player_score ) FROM player", true );
        $limit_score = 250;
        if( self::getGameStateValue( 'game_duration' ) == 2 )
            $limit_score = 350;

        return round( min( 100, 100*$max_score / $limit_score ) );
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions    (functions used everywhere)
////////////


    // Analyse played combo
    // Return an array of possible combos = object of this kind:
    //  array( "type" => set/sequence/bomb,
    //         "value" => value of the combo (the higher, the better)
    //         "serienbr" => number of different serie
    //         "nbr" => number of cards
    //         "display" => cards ids in right order for display )
    //  ... or null if this is an invalid combo
    function analyzeCombo( $cards )
    {
        $result = array();

        // Check that these cards are in player's hands
        $player_id = self::getActivePlayerId();
        $card_ids = array();
        foreach( $cards as $card )
        {
            if( $card['location'] != 'hand' || $card['location_arg'] != $player_id )
                throw new feException( 'Card is not in your hand' );
            $card_ids[] = $card['id'];
        }
        $default_display = $card_ids;

        // Build a "card grid" (serie-value and value-serie)
        $grid = array( 0 => array(), 1 => array(), 2 => array(), 3 => array(), 4 => array(), 'wild' => array() );
        $gridvalue = array( 2=>array(),3=>array(),4=>array(),5=>array(),6=>array(),7=>array(),8=>array(),9=>array(),10=>array(),11=>array(),12=>array(),13=>array() );
        $wild_cards_ids = array();
        foreach( $cards as $card )
        {
            $grid[ $card['type'] ][ $card['type_arg'] ] = $card['id'];
            $gridvalue[ $card['type_arg'] ][ $card['type'] ] = $card['id'];

            if( $card['type'] == 'wild' )
                $wild_cards_ids[] = $card['id'];
        }

        // Number of series used
        $nbr_cards = count( $cards );
        $nbr_serie_used = 0;
        $max_value = null;
        $min_value = null;
        foreach( $grid as $serie => $cards )
        {
            if( $serie !== 'wild' && count( $cards ) > 0 )
            {
                $nbr_serie_used ++;
                foreach( $cards as $value => $dummy )
                {
                    if( $max_value == null )
                        $max_value = $value;
                    if( $min_value == null )
                        $min_value = $value;
                    $max_value = max( $value, $max_value );
                    $min_value = min( $value, $min_value );
                }
            }
        }

        if( $nbr_serie_used == 0 )
        {
            // Only wild cards => must be a bomb with wild cards or a single wild card
            if( count( $grid['wild'] ) == 1 )
            {
                // This is a single set
                $set_value = null;
                if( isset( $grid['wild'][11] ) )  // J
                    $set_value = 11;
                else if( isset( $grid['wild'][12] ) )  // Q
                    $set_value = 12;
                else if( isset( $grid['wild'][13] ) )  // K
                    $set_value = 13;

                $result[] = array( 'type' => 'set', 'value' => $set_value, 'serienbr' => $nbr_serie_used, 'nbr' => $nbr_cards, 'display' => $default_display );
            }
            else
            {
                // This should be a bomb (2 to 3 wild cards)
                $bomb_value = null;
                if( isset( $grid['wild'][11] ) && isset( $grid['wild'][12] ) && isset( $grid['wild'][13] ) )  // JQK
                    $bomb_value = 5;
                else if( isset( $grid['wild'][11] ) && isset( $grid['wild'][12] ) )  // JQ
                    $bomb_value = 2;
                else if( isset( $grid['wild'][11] ) && isset( $grid['wild'][13] ) )  // JK
                    $bomb_value = 3;
                else if( isset( $grid['wild'][12] ) && isset( $grid['wild'][13] ) )  // QK
                    $bomb_value = 4;
                else
                    throw new feException("impossible bomb");

                if( $bomb_value !== null )
                    $result[] = array( 'type' => 'bomb', 'value' => $bomb_value, 'serienbr' => $nbr_serie_used, 'nbr' => $nbr_cards, 'display' => $default_display );
            }
        }
        else
        {
            // There is at least one non wild card
            if( $min_value == $max_value )
            {
                // Non wild cards are all with the same value => this is a set
                $set_value = $min_value;
                $result[] = array( 'type' => 'set', 'value' => $set_value, 'serienbr' => $nbr_serie_used, 'nbr' => $nbr_cards, 'display' => $default_display );
            }

            if( $nbr_cards == 4 && count( $grid['wild'] ) == 0 && ( $nbr_serie_used == 1 || $nbr_serie_used == 4 ) )
            {
                // 4 cards and no wild cards ... this could be a bomb !
                if( count( $gridvalue[ 3 ] )==1 && count( $gridvalue[ 5 ] )==1
                 && count( $gridvalue[ 7 ] )==1 && count( $gridvalue[ 9 ] )==1 )
                {
                    // This is a bomb !
                    $display = $default_display;
                    if( $nbr_serie_used == 1 )
                        $bomb_value = 6;    // 3 5 7 9 same color
                    else if( $nbr_serie_used == 4 )
                    {
                        $bomb_value = 1;    // 3 5 7 9 different colors
                        $display = array(
                            reset( $gridvalue[ 3 ] ), reset( $gridvalue[ 5 ] ), reset( $gridvalue[ 7 ] ), reset( $gridvalue[ 9 ] )
                        );
                    }

                    $result[] = array( 'type' => 'bomb', 'value' => $bomb_value, 'serienbr' => $nbr_serie_used, 'nbr' => $nbr_cards, 'display' => $display );
                }
            }

            // From this point, we check sequences, sequence number by sequence number
            $original_wild_card_ids = $wild_cards_ids;
            for( $sequence_nbr = 1; $sequence_nbr <= 5; $sequence_nbr++ )
            {
                // Check there is an enough global number of cards
                $displayed = array();
                $wild_cards_ids = $original_wild_card_ids;
                $bEnoughCards = false;
                if( $sequence_nbr == 1 )
                    $bEnoughCards = ($nbr_cards >= 3 );    // At least 3 cards
                else
                    $bEnoughCards = ($nbr_cards >= $sequence_nbr*2 );    // At least 2 cards per sequence

                if( $bEnoughCards )
                {
                    $sequenceLength = floor( $nbr_cards / $sequence_nbr );
                    if( $sequenceLength * $sequence_nbr == $nbr_cards ) // Check there is exactly required number of cards
                    {
                        $sequenceFrom = $min_value;
                        $sequenceTo = $sequenceFrom+$sequenceLength-1;

                        if( $nbr_serie_used == $sequence_nbr || ($nbr_serie_used+1) == $sequence_nbr )
                        {
                            // Note: in case of "+1", one of the sequence is "wild card only"
                            $nbr_wild_used = 0;
                            $nbr_wild_available = count( $grid['wild'] );
                            for( $value = $sequenceFrom; $value<=$sequenceTo; $value ++ )
                            {
                                $wild_used_for_this_value = 0;

                                if( $value > 10 )
                                {
                                    // It's all wild cards
                                    $wild_used_for_this_value = $sequence_nbr;
                                    $nbr_wild_used += $wild_used_for_this_value;
                                    for( $i=0; $i<$sequence_nbr; $i++ )
                                    {
                                        $displayed[] = array_shift( $wild_cards_ids );
                                    }
                                }
                                else
                                {
                                    $nbr_card_played_with_value = count( $gridvalue[ $value ] );
                                    foreach( $gridvalue[ $value ] as $serie_id => $card_id )
                                    {
                                        $displayed[] = $card_id;
                                    }

                                    if( $nbr_card_played_with_value < $sequence_nbr )
                                    {
                                        $wild_used_for_this_value = ($sequence_nbr - $nbr_card_played_with_value);
                                        $nbr_wild_used += $wild_used_for_this_value;
                                        for( $i=$nbr_card_played_with_value; $i<$sequence_nbr; $i++ )
                                        {
                                            $displayed[] = array_shift( $wild_cards_ids );
                                        }
                                    }
                                }
                            }

                            if( $nbr_wild_used == $nbr_wild_available )
                                $result[] = array( 'type' => 'sequence', 'value' => $sequenceTo, 'serienbr' => $sequence_nbr, 'nbr' => $nbr_cards, 'display' => $displayed );
                        }
                    }

                }
            }
        }
        return $result;
    }

    function combo_type_to_id( $combo_type )
    {
        switch( $combo_type )
        {
            case 'set':         return 1;
            case 'sequence':    return 2;
            case 'bomb':        return 3;
            default:    throw new feException( 'invalid combo type' );
        }
    }

   function endCurrentTrick()
    {
        $players = self::loadPlayersBasicInfos();
        $bIsABombTrick = (self::getGameStateValue( 'combotype' ) == self::combo_type_to_id('bomb') );

        $trickWinner = self::getGameStateValue( 'lastComboPlayer' );
        self::setGameStateValue( 'lastTrickWinner', $trickWinner );
        self::incStat( 1, 'tricks_win', $trickWinner );
        self::incStat( 1, 'trick_bombed' );

        if( !$bIsABombTrick )
            $card_goes_to = $trickWinner;
        else
        {
            // Bomb trick !
            // Get the second highest combo player
            $sql = "SELECT combo_player_id FROM `combo` WHERE combo_display!='' ORDER BY combo_id DESC LIMIT 1,1";
            $second_best_combo_player_id = self::getUniqueValueFromDB( $sql );
            if( $second_best_combo_player_id !== null )
                $card_goes_to = $second_best_combo_player_id;
            else
            {
                // Lead bomb => cards goes to the player at the left of the trick winner
                $card_goes_to = self::getPlayerBefore( $trickWinner );
            }
        }

        // Get captured cards score
        $score = 0;
        $tablecards = $this->cards->getCardsInLocation( 'table' );
        $score = self::getCardsPoints( $tablecards );

        $sql = "UPDATE player SET player_points_captured=player_points_captured+$score WHERE player_id='$card_goes_to' ";
        self::DbQuery( $sql );

        // All cards on table are captured by trick winner
        $this->cards->moveAllCardsInLocation( 'table', 'captured', null, $card_goes_to );

        if( $card_goes_to == $trickWinner )
        {
            self::notifyAllPlayers( 'captureCards', clienttranslate('${player_name} wins the trick and gets all cards'), array(
                'player_id' => $trickWinner,
                'player_name' => $players[ $trickWinner ]['player_name'],
                'score' => $score
            ) );
        }
        else
        {
            self::notifyAllPlayers( 'winTrick', clienttranslate('${player_name} wins the trick'), array(
                'player_id' => $trickWinner,
                'player_name' => $players[ $trickWinner ]['player_name']
            ) );
            self::notifyAllPlayers( 'captureCards', clienttranslate('${player_name} gets all cards'), array(
                'player_id' => $card_goes_to,
                'player_name' => $players[ $card_goes_to ]['player_name'],
                'score' => $score
            ) );
        }
    }

    function getCardsPoints( $cards )
    {
        $score = 0;
        foreach( $cards as $card )
        {
            if( $card['type']=='wild' )
            {
                if( $card['type_arg'] == 11 )
                    $score += 2;    // J
                else if( $card['type_arg'] == 12 )
                    $score += 3;    // Q
                else if( $card['type_arg'] == 13 )
                    $score += 5;    // K
            }
            else
            {
                if( $card['type_arg']%2 == 1 )  // odd number, 3/5/7/9, are 1 point cards
                    $score ++;
            }
        }
        return $score;
    }

    // Send all remaining cards + haggistwo to round winner
    function sendRemainingCardsToRoundWinner()
    {
        $card_goes_to = self::getGameStateValue( 'lastRoundWinner' );

        // Get captured cards score
        $handcards = $this->cards->getCardsInLocation( 'hand' );
        $score = self::getCardsPoints( $handcards );
        $haggistwocards = $this->cards->getCardsInLocation( 'haggistwo' );
        $score += self::getCardsPoints( $haggistwocards );

        $sql = "UPDATE player
                SET player_points_captured=player_points_captured+$score
                WHERE player_id='$card_goes_to' ";
        self::DbQuery( $sql );

        // All cards are captured by round winner
        $this->cards->moveAllCardsInLocation( 'hand', 'captured', null, $card_goes_to );
        $this->cards->moveAllCardsInLocation( 'haggistwo', 'captured', null, $card_goes_to );

        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers( 'finalCapture', clienttranslate('${player_name} goes out first: he takes all remaining cards and HaggisTwo and gets ${score} points'), array(
            'player_id' => $card_goes_to,
            'player_name' => $players[ $card_goes_to ]['player_name'],
            'score' => $score
        ) );
    }

    function resolveBets( $winner_id )
    {
        // Get all bets
        $sql = "SELECT player_id, player_bet FROM player ";
        $bets = self::getCollectionFromDB( $sql, true );

        $players = self::loadPlayersBasicInfos();

        // Get all players who will get the points of unsuccessful bets ("betfailtargets")
        $betfailtargets = array();
        foreach( $players as $player_id => $player )
        {
            $player_bet = $bets[ $player_id ];
            $bNoBet = ( $player_bet==null || $player_bet=='no' );
            if( $player_id == $winner_id )
                $betfailtargets[] = $player_id; // Winner always gets the points from unsuccessful bets
            else if( $bNoBet )
                $betfailtargets[] = $player_id; // Player who did not bet gets the points from unsuccessful bets too
        }

        foreach( $players as $player_id => $player )
        {
            $player_bet = $bets[ $player_id ];
            $bNoBet = ( $player_bet==null || $player_bet=='no' );
            $points_win = 0;

            if( $player_id == $winner_id )
            {
                // Winner's bet
                if( $player_bet=='little' )
                    $points_win = 15;
                else if( $player_bet == 'big' )
                    $points_win = 30;

                if( $points_win > 0 )
                {
                    self::incStat( 1, 'successfulbet_number', $player_id );
                    $sql = "UPDATE player
                            SET player_score=player_score+$points_win,
                            player_points_bet=player_points_bet+$points_win
                            WHERE player_id='$player_id' ";
                    self::DbQuery( $sql );
                    self::notifyAllPlayers( 'betresult', clienttranslate('${player_name} made a successful bet a gets ${points} points'), array(
                        "player_id" => $player_id,
                        "player_name" => $players[$player_id]['player_name'],
                        "points" => $points_win
                    ) );
                }
            }
            else
            {
                // Other player's bets
                if( $bNoBet )
                {
                    // This player did not bet, and did not win: nothing to do
                }
                else
                {
                    // This player makes a bet and loose => redistribute points to "betfailtargets"
                    if( $player_bet=='little' )
                        $points_win = 15;
                    else if( $player_bet == 'big' )
                        $points_win = 30;

                    $sql = "UPDATE player
                            SET player_score=player_score+$points_win ,
                            player_points_bet=player_points_bet+$points_win
                            WHERE player_id IN ('".implode( "','",$betfailtargets )."')";
                    self::DbQuery( $sql );
                    self::notifyAllPlayers( 'betresult', clienttranslate('${player_name} made a unsuccessful bet: points go to those who did not failed'), array(
                        "player_id" => $player_id,
                        "player_name" => $players[$player_id]['player_name'],
                        "points" => $points_win,
                        "targets" => $betfailtargets
                    ) );
                }
            }

        }
    }

    // Check if there is a possibility to play a rainbow / uniform bomb among current set of cards
    function checkBombsAmongCards( $cards )
    {
        $bRainbowBomb = false;
        $bSuitedBomb = false;

        // Build a color / card table with cards present
        $inhands = array();
        for( $color=0;$color<5;$color++ )
        {
            $inhands[$color] = array( 3=>0, 5=>0, 7=>0, 9=>0 );
        }

        foreach( $cards as $card )
        {
            if( $card['type'] != 'wild' )
            {
                if( in_array( $card['type_arg'], array( 3,5,7,9 ) ) )
                    $inhands[ $card['type'] ][ $card['type_arg'] ] = 1;
            }
        }

        // Test all colors combination
        for( $card3color = 0; $card3color<5; $card3color++ )
        {
            if( $inhands[ $card3color ][3] == 1 )
            {
                for( $card5color = 0; $card5color<5; $card5color++ )
                {
                    if( $inhands[ $card5color ][5] == 1 )
                    {
                        for( $card7color = 0; $card7color<5; $card7color++ )
                        {
                            if( $inhands[ $card7color ][7] == 1 )
                            {
                                for( $card9color = 0; $card9color<5; $card9color++ )
                                {
                                    if( $inhands[ $card9color ][9] == 1 )
                                    {
                                        // Here, we have a set of 4 cards with colors that matches
                                        //echo $card3color.','.$card5color.','.$card7color.','.$card9color."\n";

                                        // Count number of distinct colors
                                        $diff_color = count( array_unique( array( $card3color, $card5color, $card7color, $card9color ) ) );
                                        //echo $diff_color."\n";

                                        if( $diff_color == 4 )
                                            $bRainbowBomb = true;
                                        if( $diff_color == 1 )
                                            $bSuitedBomb = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return array(
            'rainbow' => $bRainbowBomb,
            'suited' => $bSuitedBomb
        );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////



    function pass(  )
    {
        self::checkAction( 'pass' );

        $player_id = self::getActivePlayerId();

        // Insert a "void" combo to say "this player pass"
        self::DbQuery( "INSERT INTO combo (combo_player_id, combo_display) VALUES ('$player_id', '') " );
        $combo_no = self::DbGetLastId();

        self::notifyAllPlayers( 'pass', clienttranslate('${player_name} pass'), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'combo_no' => $combo_no
        ) );

        self::incGameStateValue( 'nbrPass', 1 );

        $this->gamestate->nextState( '' );
    }

    function bet( $bet )
    {
        //self::checkAction( 'bet' );       // We don't check action cause it can be done when it is not your turn !

        // Conditions to bet:
        // 1°) no cards play (hand = 17 cards)
        // 2°) no bet made already

        // 1°)
        global $g_user;
        $player_id = $g_user->get_id();

        $handcount = $this->cards->countCardInLocation( 'hand', $player_id );
        if( $handcount < 17 )
            throw new feException( "Can't make bet: you already played some cards" );

        // 2°)
        $current_bet = self::getUniqueValueFromDB( "SELECT player_bet FROM player WHERE player_id='$player_id' " );
        if( $current_bet !== null )
            throw new feException( "Can't make bet: you already bet" );

        // Place the bet
        if( $bet == 0 )
        {
            $bet_type = 'no';
            $notify = clienttranslate( '${player_name} makes no bet' );
        }
        else if( $bet == 15 )
        {
            $bet_type = 'little';
            $notify = clienttranslate( '${player_name} makes a little bet' );
            self::incStat( 1, 'littlebet_number', $player_id );
        }
        else if( $bet == 30 )
        {
            $bet_type = 'big';
            $notify = clienttranslate( '${player_name} makes a big bet' );
            self::incStat( 1, 'bigbet_number', $player_id );
        }
        else
            throw new feException( "Wrong bet value" );

        self::DbQuery( "UPDATE player SET player_bet='$bet_type' WHERE player_id='$player_id' ");

        // Notify
        self::notifyAllPlayers( "bet", $notify, array(
            "player_id" => $player_id,
            "player_name" => self::getCurrentPlayerName(),
            "bet" => $bet_type
        ) );
    }

    function playCombo( $cards_ids, $combo_id )
    {
        self::checkAction( 'playCombo' );
        $player_id = self::getActivePlayerId();

        if( count( $cards_ids ) == 0 )
            throw new feException( self::_('No cards selected'), true );

        $cards = $this->cards->getCards( $cards_ids );

        $combos = self::analyzeCombo( $cards );

        $currentComboType = self::getGameStateValue( 'combotype' );
        $bOpeningCombination = ( $currentComboType == 0 );

        if( count( $combos ) == 0 )
            throw new feException( self::_("This is not a valid card combination. You should play a set, a sequence or a bomb"), true );
        else if( count( $combos ) > 1 )
        {
            // There is a choice between several combo

            if( $combo_id === null )
            {
                // => give player a choice
                self::notifyPlayer( $player_id, 'multipleCombos', '', $combos );
                return;
            }
            else
            {
                if( ! isset( $combos[ $combo_id ] ) )
                    throw new feException( "Wrong combo" );
                $combo = $combos[ $combo_id ];
            }
        }
        else
        {
            // There is only one combo => play this one
            $combo = reset( $combos );
        }

        $combo_type_id = self::combo_type_to_id( $combo['type'] );

        if( $combo['type'] == 'bomb' )
            self::incStat( 1, 'bomb_number', $player_id );

        if( ! $bOpeningCombination )
        {
            $bTrickJustBombed = false;
            $current_combo_type = self::getGameStateValue( 'combotype' );
            // This is not an opening combination => we must perform checks
            if( $combo_type_id != $current_combo_type )
            {
                if( $combo['type'] != 'bomb' )
                {
                    if( $current_combo_type == 1 )
                        throw new feException( self::_("Wrong card combination type: you should play a set or a bomb"), true );
                    else if( $current_combo_type == 2 )
                        throw new feException( self::_("Wrong card combination type: you should play a sequence or a bomb"), true );
                    else
                        throw new feException( self::_("Wrong card combination type: you should play a bomb"), true );
                }
                else
                {
                    // A bomb can be played anytime. From now only bombs can be played.
                    self::setGameStateValue( 'combotype', self::combo_type_to_id('bomb') );
                    $bTrickJustBombed = true;
                }
            }
            if( $combo['type'] != 'bomb' && $combo['nbr'] != self::getGameStateValue( 'combonbr' ) )
                throw new feException( sprintf( self::_("You must play %s cards"), self::getGameStateValue( 'combonbr' ) ), true );
            if( $combo['type'] == 'sequence' )
            {
                if( $combo['serienbr'] != self::getGameStateValue( 'comboserienbr' ) )
                    throw new feException( sprintf( self::_("You must play %s sequences"), self::getGameStateValue( 'comboserienbr' ) ), true );
            }
            if( $combo['value'] <= self::getGameStateValue( 'combovalue' ) )
            {
                if( !$bTrickJustBombed )
                    throw new feException( self::_("You must play a higher combination than the previous one"), true );
            }

            self::setGameStateValue( 'combovalue', $combo['value'] );
        }
        else
        {
            // First set
            self::setGameStateValue( 'combotype', $combo_type_id );
            self::setGameStateValue( 'combonbr', $combo['nbr'] );
            self::setGameStateValue( 'comboserienbr', $combo['serienbr'] );
            self::setGameStateValue( 'combovalue', $combo['value'] );
        }

        self::setGameStateValue( 'nbrPass', 0 );       // number of consecutive pass action => reset

        $card_count = $this->cards->countCardsByLocationArgs( 'hand' );
        $player_still_in_round = count( $card_count );
        self::setGameStateValue( 'nbrPassToWin', $player_still_in_round-1 );       // number of consecutive pass action to win => reset

        // From this step, player manage to play a regular combination => put this combo on the table
        $combo_display = implode( ',', $combo['display'] );
        self::DbQuery( "INSERT INTO combo (combo_player_id, combo_display) VALUES ('$player_id', '$combo_display') " );
        $combo_no = self::DbGetLastId();

        $this->cards->moveCards( $cards_ids, 'table', $combo_no );

        $cards_number = count( $cards_ids );

        $combo_description_i18n = array('combination');
        $notification_description = clienttranslate('${player_name} plays a ${combination}');
        $notification_args = array(
            'i18n' => $combo_description_i18n,
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'cards' => $cards,
            'display' => $combo['display'],
            'combo_no' => $combo_no,
            'combination' => $combo['type'],
            'nbr' => $cards_number
        );

        if( $combo['type'] == 'sequence' )
        {
            if( $combo['serienbr'] > 1 )
            {
                $notification_description = clienttranslate('${player_name} plays a sequence of ${ofakind} of a kind');
                $notification_args['combination'] = clienttranslate('sequences');
                $notification_args['ofakind'] = $cards_number / $combo['serienbr'];
            }
        }

        self::notifyAllPlayers( 'playCombo', $notification_description, $notification_args );

        self::setGameStateValue( 'lastComboPlayer', $player_id );

        if( $this->cards->countCardInLocation( 'hand', $player_id ) == 0 )
        {
            // This player gets out of the trick

            // ... scoring !

            // Count the number of cards in hand on opponent hands with the biggest number of cards
            $hand_count = $this->cards->countCardsByLocationArgs( 'hand' );
            $opponent_with_biggest = getKeyWithMaximum( $hand_count, true, false );
            $card_number = $hand_count[ $opponent_with_biggest ];
            $number_of_player_in_round = count( $hand_count );
            $players = self::loadPlayersBasicInfos();

            $score = 5*$card_number;
            self::notifyAllPlayers( 'playerGoOut', clienttranslate('${player_name} has shed the cards from his hand and gets ${score} points (5 x ${card_number} cards in the hand of ${opponent_player})'), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'score' => $score,
                'card_number' => $card_number,
                'opponent_player' => $players[ $opponent_with_biggest ]['player_name']
            ) );

            $bFirstToGoOut = false;
            if( $number_of_player_in_round == 1 && count( $players )==2 )
                $bFirstToGoOut = true;
            if( $number_of_player_in_round == 2 && count( $players )==3 )
                $bFirstToGoOut = true;

            if( $bFirstToGoOut )
            {
                self::setGameStateValue( 'lastRoundWinner', $player_id );
                self::resolveBets( $player_id );
            }

            self::DbQuery( "UPDATE player
                            SET player_score=player_score+$score ,
                            player_points_remaining=player_points_remaining+$score
                            WHERE player_id='$player_id' " );
        }

        $this->gamestate->nextState('');
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////



//////////////////////////////////////////////////////////////////////////////
//////////// Game state reactions   (reactions to game planned states from state machine
////////////

    function stNewRound()
    {
        // New round:
        // Shuffle and deal cards to all players

        // Reset "last points win" values
        $sql = "UPDATE player SET player_points_bet='0', player_points_captured='0', player_points_remaining='0' ";
        self::DbQuery( $sql );

        // Put jacks/queens/kings in special location
        $sql = "UPDATE card SET card_location='wildpool' WHERE card_type='wild' ";
        self::DbQuery( $sql );

        $this->cards->shuffle('deck');
        self::incStat( 1, 'round_number' );

        $players = self::loadPlayersBasicInfos();
        foreach( $players as $player_id => $player )
        {
            $this->cards->pickCards( 14, 'deck', $player_id );
        }

        // Give one J/Q/K to each player
        $wildcards = $this->cards->getCardsInLocation( 'wildpool' );
        $jacks = array();
        $queens = array();
        $kings = array();
        foreach( $wildcards as $wildcard )
        {
            if( $wildcard['type_arg'] == 11 )
                $jacks[] = $wildcard['id'];
            if( $wildcard['type_arg'] == 12 )
                $queens[] = $wildcard['id'];
            if( $wildcard['type_arg'] == 13 )
                $kings[] = $wildcard['id'];
        }

        foreach( $players as $player_id => $player )
        {
            $cards_to_move = array();
            $cards_to_move[] = array_pop( $jacks );
            $cards_to_move[] = array_pop( $queens );
            $cards_to_move[] = array_pop( $kings );
            $this->cards->moveCards( $cards_to_move, 'hand', $player_id );
            $anyplayer= $player_id;
        }

        // Reset bets
        $sql = "UPDATE player SET player_bet=NULL ";
        self::DbQuery( $sql );

        // Notify: new hand
        self::notifyAllPlayers( 'newRound', clienttranslate('A new round starts'), array() );

        foreach( $players as $player_id => $player )
        {
            $cards = $this->cards->getCardsInLocation( 'hand', $player_id );
            $bombcheck = self::checkBombsAmongCards( $cards );

            self::notifyPlayer( $player_id, 'newDeal', '', array(
                'cards' => $cards,
                'bombcheck' => $bombcheck
            ) );

        }


        // Get scores
        $player_to_score = self::getCollectionFromDB( "SELECT player_id,player_score FROM player", true );
        $min_player_id = getKeyWithMaximum( $player_to_score, false );

        // Dealer = leader in points. If tie: dealer = winner of the last round
        $current_dealer = self::getGameStateValue( 'dealer' );
        if( $current_dealer == 0 )
        {
            // First round: any player = dealer
            $dealer_id = $anyplayer;
            self::setGameStateValue( 'dealer', $dealer_id );
        }
        else
        {
            $dealer_id = self::getGameStateValue( 'lastTrickWinner' );
            self::setGameStateValue( 'dealer', $dealer_id );
        }

        // First player = player with fewest point. If tie: player to the left of the dealer
        if( $min_player_id == null || $current_dealer == 0 )    // note: tie or first round
        {
            // Tie ! => player to the left of the dealer
            $new_active_player = self::getPlayerAfter( $dealer_id );
        }
        else
        {
            $new_active_player = $min_player_id;
        }
        $this->gamestate->changeActivePlayer( $new_active_player );

        // Remaining cards => haggistwo
        $this->cards->moveAllCardsInLocation( 'deck', 'haggistwo' );

        self::setGameStateValue( 'lastTrickWinner', 0 );

        $this->gamestate->nextState( '' );
    }

    function stNewTrick()
    {
        // New trick:
        // player who wins the previous trick => active
        // if this player is out of the round => player to his left

        $lastwinner = self::getGameStateValue( 'lastTrickWinner' );
        self::incStat( 1, 'trick_number' );

        if( $lastwinner == 0 )
        {
            // First trick of this round => keep the current active player
        }
        else
        {
            $hand_count = $this->cards->countCardsByLocationArgs( 'hand' );
            if( isset( $hand_count[ $lastwinner ] ) )
                $this->gamestate->changeActivePlayer( $lastwinner );
            else
                $this->gamestate->changeActivePlayer( self::getPlayerAfter( $lastwinner ) );
        }

        // Reset current combo values
        self::setGameStateValue( 'combotype', 0 );   // 0 = no combo / 1 = set / 2 = sequence / 3 = bomb
        self::setGameStateValue( 'combonbr', 0 );         // number of cards of current combo
        self::setGameStateValue( 'comboserienbr', 0 );    // number of series of current combo
        self::setGameStateValue( 'combovalue', 0 );       // value of current combo
        self::setGameStateValue( 'nbrPass', 0 );       // number of consecutive pass action

        self::DbQuery( "DELETE FROM combo WHERE 1" );   // Remove all combo on table

        $this->gamestate->nextState( '' );
    }



    function stNextPlayer()
    {
        // Next player:

        // Cards in hand.
        //  note: if a player has some cards in hand, he is still in the trick
        $card_count = $this->cards->countCardsByLocationArgs( 'hand' );
        $player_still_in_round = count( $card_count );

        $nbrPassToWin = self::getGameStateValue('nbrPassToWin');

        $players = self::loadPlayersBasicInfos();

        if( $player_still_in_round == 1 )
        {
            // All players but one get out of the trick => end of the round
            self::endCurrentTrick();

            // All remaining cards + haggistwo goes to round winner
            self::sendRemainingCardsToRoundWinner();

            $this->gamestate->nextState( 'endRound' );
        }
        else
        {
            if( self::getGameStateValue( 'nbrPass' ) == $nbrPassToWin )
            {
                // All players passes except the winner of the trick
                // => end of the trick
                self::endCurrentTrick();

                $this->gamestate->nextState( 'endTrick' );
            }
            else
            {
                // Continue the current trick play
                // => go to next player with cards in hand
                $current_player = self::getActivePlayerId();
                $next_player = self::createNextPlayerTable( array_keys( $players ) );
                $bContinue = true;
                while( $bContinue )
                {
                    $current_player = $next_player[ $current_player ];

                    if( isset( $card_count[ $current_player ] ) )
                    {
                        // This player still have some cards in hand
                        // => We found our next player
                        $bContinue = false;
                        $this->gamestate->changeActivePlayer( $current_player );
                        $this->gamestate->nextState( 'nextPlayer' );
                        self::giveExtraTime( $current_player );
                    }
                }
            }
        }
    }

    function testRecap()
    {
        // Build the score recap'
        $sql = "SELECT player_id, player_score, player_points_captured, player_points_bet, player_points_remaining FROM player";
        $recap = self::getCollectionFromDB( $sql );
        self::notifyAllPlayers( 'scoreRecap', '', $recap );
        $this->sendNotifications();
   }

    function stEndRound()
    {
        // End round: score remaining points

        // Captured points => added to score
        $sql = "UPDATE player SET player_score=player_score+player_points_captured ";
        self::DbQuery( $sql );

        // Build the score recap'
        $sql = "SELECT player_id, player_score, player_points_captured, player_points_bet, player_points_remaining FROM player ";
        $recap = self::getCollectionFromDB( $sql );
        self::notifyAllPlayers( 'scoreRecap', '', $recap );

        // Gather all cards and put them in the deck
        $this->cards->moveAllCardsInLocation( 'hand', 'deck' );
        $this->cards->moveAllCardsInLocation( 'captured', 'deck' );
        $this->cards->moveAllCardsInLocation( 'table', 'deck' );
        $this->cards->moveAllCardsInLocation( 'haggistwo', 'deck' );


        // If one player is above the limit (250 or 350), end the game

        $player_to_score = self::getCollectionFromDB( "SELECT player_id,player_score FROM player", true );
        $max_score = max( $player_to_score );

        $limit_score = 250;
        if( self::getGameStateValue( 'game_duration' ) == 2 )
            $limit_score = 350;
            
        $nbr_player_with_highest_score = 0;
        foreach( $player_to_score as $player_id => $player_score )
        {
            if( $player_score == $max_score )
                $nbr_player_with_highest_score++;
        }
        
        if( $nbr_player_with_highest_score > 1 )
            $max_score = 0;

        if( $max_score >= $limit_score ) // Note: in case there is a tie, max_score is null and the game continue
            $this->gamestate->nextState( 'endGame' );
        else
            $this->gamestate->nextState( 'newRound' );

    }


//////////////////////////////////////////////////////////////////////////////
//////////// End of game management
////////////


    protected function getGameRankInfos()
    {

        //  $result = array(   "table" => array( "stats" => array( 1 => 0.554, 2 => 54, 3 => 56 ) ),       // game statistics
        //                     "result" => array(
        //                                     array( "rank" => 1,
        //                                            "tie" => false,
        //                                            "score" => 354,
        //                                            "player" => 45,
        //                                            "name" => "Kara Thrace",
        //                                            "zombie" => 0,
        //                                            "stats" => array( 1 => 0.554, 2 => 54, 3 => 56 ) ),
        //                                     array( "rank" => 2,
        //                                            "tie" => false,
        //                                            "score" => 312,
        //                                            "player" => 46,
        //                                            "name" => "Lee Adama",
        //                                            "zombie" => 0,
        //                                            "stats" => array( 1 => 0.554, 2 => 54, 3 => 56 ) )
        //                                     )
        //              )
        //


        // By default, common method uses 'player_rank' field to create this object
        $result = self::getStandardGameResultObject();

        // Adding stats



        return $result;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    function zombieTurn( $state, $active_player )
    {
        if( $state['name'] == 'playComboOpen' )
        {
            $this->gamestate->nextState( "" );
        }
        else if( $state['name'] == 'playCombo' )
        {
            self::pass();
        }
        else
            throw new feException( "Zombie mode not supported at this game state:".$state['name'] );
    }

    function dummy()
    {
        clienttranslate( 'set' );
        clienttranslate( 'bomb' );
        clienttranslate( 'sequence' );
    }

}

?>
