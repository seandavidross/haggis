<?php

namespace Haggis\Exception 
{
  use Exception;

  class ImpossibleCombination extends Exception {}

  class CardNotInHand extends Exception {}

  class NullCombination extends Exception {}

  class EmptyCombination extends Exception {}


  class InvalidSuit extends Exception 
  {
    public function __construct() 
    {
      parent::__construct("Cards can only be made using suit values from Haggis\Cards\SUITS");
    }

  }


  class InvalidRank extends Exception 
  {
    public function __construct() 
    {
      parent::__construct("Cards can only be made using rank values from Haggis\Cards\RANKS");
    }
  }
}

?>