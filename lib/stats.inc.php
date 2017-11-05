<?php

/////////////////////////////////////////////////////////////////////
///// Game statistics description
/////

$stats_type // Statistics global to table
  = array( "table" 
             => array( "round_number" 
                         => array( "id" => 10
                                 , "name" => totranslate("Number of rounds")
                                 , "type" => "int" 
                                 )
                     , "trick_number" 
                         => array( "id" => 11
                                 , "name" => totranslate("Number of tricks")
                                 , "type" => "int" 
                                 )
                     , "trick_bombed" 
                         => array( "id" => 12
                                 , "name" => totranslate("Tricks won with a bomb") 
                                 , "type" => "int" 
                                 )
                     )
    
           // Statistics existing for each player
         , "player" 
             => array( "tricks_win" 
                         => array( "id" => 10
                                 , "name" => totranslate("Number of tricks won")
                                 , "type" => "int" 
                                 )
                     , "bomb_number" 
                         => array( "id" => 11
                                 , "name" => totranslate("Number of bomb played")
                                 , "type" => "int" 
                                 )
                     , "littlebet_number" 
                         => array( "id" => 12,
                                 , "name" => totranslate("Little bet number")
                                 , "type" => "int" 
                                 )
                     , "bigbet_number" 
                         => array( "id" => 13
                                 , "name" => totranslate("Big bet number")
                                 , "type" => "int" 
                                 )
                     , "successfulbet_number" 
                         => array( "id" => 14
                                 , "name" => totranslate("Number of successful bet"), 
                                 , "type" => "int" 
                                 )
    
                     )

         );

?>
