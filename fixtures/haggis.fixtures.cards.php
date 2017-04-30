<?php

require_once "./lib/haggistwo.cards.php";

use Haggis\Cards\Card as Card;

const SUITS = Haggis\Cards\SUITS;
const RANKS = Haggis\Cards\RANKS;


$GLOBALS['RED_THREE'] = 
  new Card( SUITS['RED'], RANKS['3'] );

$GLOBALS['RED_FIVE'] = 
  new Card( SUITS['RED'], RANKS['5'] );

$GLOBALS['RED_SIX'] = 
  new Card( SUITS['RED'], RANKS['6'] );

$GLOBALS['RED_SEVEN'] = 
  new Card( SUITS['RED'], RANKS['7'] );

$GLOBALS['RED_NINE'] = 
  new Card( SUITS['RED'], RANKS['9'] );

$GLOBALS['GREEN_FIVE'] = 
  new Card( SUITS['GREEN'], RANKS['5'] );

$GLOBALS['GREEN_SIX'] = 
  new Card( SUITS['GREEN'], RANKS['6'] );

$GLOBALS['BLUE_FIVE'] = 
  new Card( SUITS['BLUE'], RANKS['5'] );

$GLOBALS['BLUE_SEVEN'] = 
  new Card( SUITS['BLUE'], RANKS['7'] );

$GLOBALS['ORANGE_NINE'] = 
  new Card( SUITS['ORANGE'], RANKS['9'] );

$GLOBALS['WILD_JACK'] = 
  new Card( SUITS['WILD'], RANKS['J'] );

$GLOBALS['WILD_QUEEN'] = 
  new Card( SUITS['WILD'], RANKS['Q'] );

$GLOBALS['WILD_KING'] = 
  new Card( SUITS['WILD'], RANKS['K'] );



?>