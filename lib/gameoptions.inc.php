<?php

/////////////////////////////////////////////////////////////////////
///// Game options description
/////

$long_game = 
  array( 'name' => totranslate('Long game (350 points)')
       , 'tmdisplay' => totranslate('Long game (350 points)') 
       );

$short_game = 
  array( 'name' => totranslate('Short game (250 points)')
       , 'tmdisplay' => totranslate('Short game (250 points)') 
       );

$game_durations =
  array( 2 => $long_game
       , 1 => $short_game
       );

$game_options = 
  array( 100 => array( 'name' => totranslate('Game duration')
                     , 'values' => $game_durations
                     )
       );
?>
