<?php
 /**
  * states.game.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  *
  *
  * Testlayout game states
  *
  */

/*
*
*   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
*   in a very easy way from this configuration file.
*
*
*   States types:
*   _ manager: game manager can make the game progress to the next state.
*   _ game: this is an (unstable) game state. the game is going to progress to the next state as soon as current action has been accomplished
*   _ activeplayer: an action is expected from the activeplayer
*
*   Arguments:
*   _ possibleactions: array that specify possible player actions on this step (for state types "manager" and "activeplayer")
*       (correspond to actions names)
*   _ action: name of the method to call to process the action (for state type "game")
*   _ transitions: name of transitions and corresponding next state
*       (name of transitions correspond to "nextState" argument)
*   _ description: description is displayed on top of the main content.
*   _ descriptionmyturn (optional): alternative description displayed when it's player's turn
*
*/

$machinestates 
  = array( 1 => array( "name" => "gameSetup"
                     , "description" => clienttranslate("Game setup")
                     , "type" => "manager"
                     , "action" => "stGameSetup"
                     , "transitions" => array( "" => 2 )
                     )
         
         , 2 => array( "name" => "newRound"
                     , "description" => ''
                     , "type" => "game"
                     , "action" => "stNewRound"
                     , "transitions" => array( "" => 10 )
                     )
         
         , 10 => array( "name" => "newTrick"
                      , "description" => ''
                      , "type" => "game"
                      , "action" => "stNewTrick"
                      , "updateGameProgression" => true
                      , "transitions" => array( "" => 11 )
                      )
         
         , 11 => array( "name" => "playComboOpen"
                      , "description" => 
                          clienttranslate('${actplayer} must play an opening card combination')
                      , "descriptionmyturn" => 
                          clienttranslate('${you} must play an opening card combination')
                      , "type" => "activeplayer"
                      , "possibleactions" => array( "playCombo", "bet" )
                      , "transitions" => array( "" => 20    )
                      )
         
         , 12 => array( "name" => "playCombo"
                      , "description" => 
                          clienttranslate('${actplayer} must play a card combination, or pass')
                      , "descriptionmyturn" => 
                          clienttranslate('${you} must play a card combination, or pass')
                      , "type" => "activeplayer"
                      , "possibleactions" => array( "playCombo", "pass", "bet" )
                      , "transitions" => array( "" => 20  )
                      )
         
         , 20 => array( "name" => "nextPlayer"
                      , "description" => ''
                      , "type" => "game"
                      , "action" => "stNextPlayer"
                      , "transitions" => 
                          array( "nextPlayer" => 12, "endTrick" => 10, "endRound" => 50 )
                      )
         
         , 50 => array( "name" => "endRound"
                      , "description" => ''
                      , "type" => "game"
                      , "action" => "stEndRound"
                      , "transitions" => array( "endGame" => 99, "newRound" => 2 )
                      )
         
         , 99 => array( "name" => "gameEnd"
                      , "description" => clienttranslate("End of game")
                      , "type" => "manager"
                      , "action" => "stGameEnd"
                      , "args" => "argGameEnd"
                      )
         );

?>
