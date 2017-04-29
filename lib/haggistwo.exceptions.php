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
      parent::__construct("A card's suit must be one of Haggis\Cards\SUITS");
    }

  }


  class InvalidRank extends Exception 
  {
    public function __construct() 
    {
      parent::__construct("A card's rank must be one of Haggis\Cards\RANKS");
    }
  }

  
  class InvalidOwner extends Exception 
  {
    public function __construct() 
    {
      parent::__construct("A card's owner must be one of Haggis\Cards\OWNERS");
    }
  }


  class InvalidLocation extends Exception 
  {
    public function __construct() 
    {
      parent::__construct("A card's location must be one of Haggis\Cards\LOCATIONS");
    }
  }


  class InvalidPosition extends Exception 
  {
    public function __construct() 
    {
      parent::__construct("A card's position must be between 0 and 16");
    }
  }

}

?>