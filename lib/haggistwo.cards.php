<?php
namespace Haggis\Cards
{
  require_once "./lib/haggistwo.exceptions.php";

  use Haggis\Exception\InvalidSuit as InvalidSuit;
  use Haggis\Exception\InvalidRank as InvalidRank;

  const SUITS =
    array('RED' => 0
         ,'ORANGE' => 1
         ,'YELLOW' => 2
         ,'GREEN' => 3
         ,'BLUE' => 4
         ,'WILD' => 'wild'
         );


  const RANKS =
    array('2' => 2
         ,'3' => 3
         ,'4' => 4
         ,'5' => 5
         ,'6' => 6
         ,'7' => 7
         ,'8' => 8
         ,'9' => 9
         ,'T' => 10
         ,'J' => 11
         ,'Q' => 12
         ,'K' => 13
         );


  const POINT_CARDS =
    array(3, 5, 7, 9);


  const POINTS =
    array(2 => 0
         ,3 => 1
         ,4 => 0
         ,5 => 1
         ,6 => 0
         ,7 => 1
         ,8 => 0
         ,9 => 1
         ,10 => 0 // T
         ,11 => 2 // J
         ,12 => 3 // Q
         ,13 => 5 // K
         );


  const WILD_CARDS =
    array('JACK' => 11
         ,'QUEEN' => 12
         ,'KING' => 13
         );

  // REFACTOR: these don't belong here...
  const OWNERS =
    array('DEALER' => 0
         ,'FOREHAND' => 1
         ,'MIDDLEHAND' => 2
         ,'HAGGIS' => 3
         );


  const LOCATIONS =
    array('HAND' => 0
         ,'PILE' => 1
         ,'TRICKS' => 2
         ,'HAGGIS' => 3
         );



  class Card 
  {
    function __construct(int $suit, int $rank, $options = array()) 
    {
      $this->suit_ = $this->check_suit_($suit);

      $this->rank_ = $this->check_rank_($rank);

      $this->points_ = POINTS[$rank];

      $this->owner_ 
        = isset($options['owner']) 
        ? $options['owner'] 
        : OWNERS['FOREHAND'];

      $this->location_ 
        = isset($options['location']) 
        ? $options['location'] 
        : LOCATIONS['HAND'];
      
      $this->position_ 
        = isset($options['position']) 
        ? $options['position'] 
        : 0;
    }


    private function check_suit_($suit) 
    {
      if( !in_array($suit, array_values(SUITS)) ) throw new InvalidSuit();
      
      return $suit;
    }


    private function check_rank_($rank)
    {
      if( !in_array($rank, array_values(RANKS))) throw new InvalidRank();

      return $rank;
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


    function change_owner($new_owner)
    {
      $this->owner_ = $new_owner;
    }


    function location()
    {
      return $this->location_;
    }


    function change_location($new_location)
    {
      $this->location_ = $new_location;
    }


    function position()
    {
      return $this->position_;
    }


    function change_position($new_position)
    {
      $this->position_ = $new_position;
    }


    function to_a() 
    { // Eventually the key names need to be updated and need to include 'points'
      return 
        array('type' => $this->suit_
             ,'type_arg' => $this->rank_
             ,'id' => $this->owner_
             ,'location' => $this->location_
             ,'location_arg' => $this->position_
             );
    }


    private $suit_;
    private $rank_;
    private $points_;
    // REFACTOR? Not sure cards should know the following...
    private $owner_;    // FOREHAND|MIDDLEHAND|REARHAND(dealer)
    private $location_; // HAND|PILE|TRICKS|HAGGIS 
    private $position_; // 0-16 (14-16 are face cards)
  }

}

?>
