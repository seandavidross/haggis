<?php

namespace Haggis\Exception 
{
  use Exception;

  class ImpossibleCombination extends Exception 
  {
    public function __construct() 
    {
      parent::__construct("impossible bomb");
    }
  }


  class CardNotInHand extends Exception 
  {
    public function __construct() 
    {
      parent::__construct("Card is not in your hand");
    }
  }


  class NullCombination extends Exception 
  {
    public function __construct() 
    {
      parent::__construct("A combo's cards cannot be null.");
    }
  }


  class EmptyCombination extends Exception 
  {
    public function __construct() 
    {
      parent::__construct("A combo cannot contain zero cards");
    }
  }


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