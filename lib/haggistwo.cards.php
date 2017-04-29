<?php
namespace Haggis\Cards
{
  require_once "./lib/haggistwo.exceptions.php";

  use Haggis\Exception\InvalidSuit as InvalidSuit;
  use Haggis\Exception\InvalidRank as InvalidRank;
  use Haggis\Exception\InvalidOwner as InvalidOwner;
  use Haggis\Exception\InvalidLocation as InvalidLocation;
  use Haggis\Exception\InvalidPosition as InvalidPosition;


  const SUITS =
    array( 'RED' => 0
         , 'ORANGE' => 1
         , 'YELLOW' => 2
         , 'GREEN' => 3
         , 'BLUE' => 4
         , 'WILD' => 'wild'
         );

  const RANKS =
    array( '2' => 2
         , '3' => 3
         , '4' => 4
         , '5' => 5
         , '6' => 6
         , '7' => 7
         , '8' => 8
         , '9' => 9
         , 'T' => 10
         , 'J' => 11
         , 'Q' => 12
         , 'K' => 13
         );

  const POINTS =
    array( 2 => 0
         , 3 => 1
         , 4 => 0
         , 5 => 1
         , 6 => 0
         , 7 => 1
         , 8 => 0
         , 9 => 1
         , 10 => 0 // T
         , 11 => 2 // J
         , 12 => 3 // Q
         , 13 => 5 // K
         );

  const WILD_CARDS =
    array( 'JACK' => 11
         , 'QUEEN' => 12
         , 'KING' => 13
         );

  // REFACTOR: these don't belong here...
  const OWNERS =
    array( 'DEALER' => 0
         , 'FOREHAND' => 1
         , 'MIDDLEHAND' => 2
         , 'HAGGIS' => 3
         );

  const LOCATIONS =
    array( 'HAND' => 0
         , 'PILE' => 1
         , 'TRICKS' => 2
         , 'HAGGIS' => 3
         );

  const POINT_CARDS = array(3, 5, 7, 9);
    // REFACTOR: move to Haggis/Hand/MAX_SIZE...
  const MAX_HAND_SIZE = 17;


  class Card
  {
    function __construct(int $suit, int $rank, $options = array())
    {
      $this->suit_ = $this->check_suit_($suit);
      $this->rank_ = $this->check_rank_($rank);
      $this->points_ = POINTS[$rank];

      $this->owner_
        = isset($options['owner'])
        ? $this->check_owner_($options['owner'])
        : OWNERS['FOREHAND'];

      $this->location_
        = isset($options['location'])
        ? $this->check_location_($options['location'])
        : LOCATIONS['HAND'];

      $this->position_
        = isset($options['position'])
        ? $this->check_position_($options['position'])
        : 0; // leftmost card
    }


    private function check_suit_($suit)
    {
      if( !in_array($suit, array_values(SUITS)) )
        throw new InvalidSuit();

      return $suit;
    }


    private function check_rank_($rank)
    {
      if( !in_array($rank, array_values(RANKS)))
        throw new InvalidRank();

      return $rank;
    }


    private function check_owner_(int $owner)
    {
      if( !in_array($owner, array_values(OWNERS)))
        throw new InvalidOwner();

      return $owner;
    }


    private function check_location_(int $location)
    {
      if( !in_array($location, array_values(LOCATIONS)))
        throw new InvalidLocation();

      return $location;
    }


    // NOTE: this truly doesn't belong here. It belongs in a Hand class...
    // the position >= MAX_HAND_SIZE should really be position >= $this->size_
    // (where $this is an instance of class 'Hand')
    private function check_position_(int $position)
    {
      if( $position < 0 || $position >= MAX_HAND_SIZE)
        throw new InvalidPosition();

      return $position;
    }


    function suit()
    {
      return $this->suit_;
    }


    function rank()
    {
      return $this->rank_;
    }

    function points()
    {
      return $this->points_;
    }


    function owner()
    {
      return $this->owner_;
    }


    function with_owner(int $new_owner)
    {
      $clone = clone $this;
      $clone->owner_ = $this->check_owner_($new_owner);
      return $clone;
    }


    function location()
    {
      return $this->location_;
    }


    function with_location(int $new_location)
    {
      $clone = clone $this;
      $clone->location_ = $this->check_location_($new_location);
      return $clone;
    }


    function position()
    {
      return $this->position_;
    }


    function with_position(int $new_position)
    {
      $clone = clone $this;
      $clone->position_ = $this->check_postition_($new_position);
      return $clone;
    }


    function to_hash()
    { // Eventually the key names need to be updated and need to include 'points'
      return
        array('type' => $this->suit_
             ,'type_arg' => $this->rank_
             ,'id' => $this->position_
             ,'location' => $this->location_
             ,'location_arg' => $this->owner_
             );
    }


  }

}

?>
