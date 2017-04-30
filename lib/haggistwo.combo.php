<?php
namespace Haggis\Cards 
{
  require_once "./lib/haggistwo.exceptions.php";
  use Haggis\Exception\ImpossibleCombination as ImpossibleCombination;
  use Haggis\Exception\CardNotInHand as CardNotInHand;
  use Haggis\Exception\NullCombination as NullCombination;
  use Haggis\Exception\EmptyCombination as EmptyCombination;


  class Combo
  {
    function __construct(array $cards)
    { 
      if (empty($cards))
        throw new EmptyCombination("A combo cannot contain zero cards");
      
      if (in_array(null, $cards)) 
        throw new NullCombination("A combo's cards cannot be null.");

      $this->cards = $cards;
      $this->lowest_rank = 0;
      $this->possibles = array();
      $this->cards_by_suit = array();
      $this->cards_by_rank = array();
      $this->wild_card_ids = array();
      $this->default_display = array();
      $this->card_display_order = array();
      $this->has_cached_possibles = false;
      $this->number_of_wilds_available = 0;      
    }

    // Analyse played combo
    // Return an array of possible combos = object of this kind:
    //  array( "type" => set/sequence/bomb,
    //         "value" => value of the combo (the higher, the better)
    //         "serienbr" => number of different serie
    //         "nbr" => number of cards
    //         "display" => cards ids in right order for display )
    //  ... or null if this is an invalid combo
    function get_possible_combinations($cards)
    {
      if( $this->has_cached_possibles )
        return $this->possibles;

      $this->prepare_to_analyze_($cards);

      if( $this->is_all_wild_cards_() ) 
        return $this->could_be_wild_single_or_wild_bomb_();
      
      if( $this->could_be_number_bomb_() ) 
        return $this->could_be_rainbow_or_suited_bomb_();
      
      return $this->could_be_set_or_sequence_();
    } 


    private function prepare_to_analyze_($cards) 
    {
      static::check_all_cards_belong_to_active_player_($cards);

      $this->group_cards_by_suit_and_rank_($cards);

      $this->count_suits_($cards);

      $this->default_display 
        = $this->arrange_cards_by_id_( $cards );

      $this->number_of_cards = count($cards);

      $this->number_of_wilds_available 
        = count( $this->cards_by_suit[SUITS['WILD']] );

      $this->possibles = array();

      $this->has_cached_possibles = true;
    }


  // The following 3 methods probably belong in the controller class,
  // the class that will call #get_possible_combinations, this class shouldn't care
  // about who has the cards or where they came from, it only needs to
  // know if the cards form valid Haggis combinations or not...
    private static function check_all_cards_belong_to_active_player_($cards)
    {
      foreach( $cards as $card )
      {
        if(static::does_not_belong_to_active_player_($card))
          throw new CardNotInHand('Card is not in your hand');
      }
    }

    private static function does_not_belong_to_active_player_($card) 
    {
      return $card['location'] != 'hand' 
          || $card['location_arg'] != static::get_active_player_id_();
    }

    private static function get_active_player_id_() 
    {
      return 1; // HACK: just need a consistent value for testing
    }
  // REFACTOR: move the above methods into HaggisTwo class.


    private function group_cards_by_suit_and_rank_($cards) 
    { // Build a "card grid" (serie-value and value-serie)
      $this->cards_by_suit  = array_fill_keys( SUITS, array() );
      $this->cards_by_rank  = array_fill_keys( RANKS, array() );
      $this->wild_card_ids = array();

      foreach( $cards as $card ) 
      {
        $suit = $card['type'];
        $rank = $card['type_arg'];

        $this->cards_by_suit[$suit][$rank] = $card['id'];
        $this->cards_by_rank[$rank][$suit] = $card['id'];
        
        if( $suit == SUITS['WILD'] ) 
          $this->wild_card_ids[] = $card['id'];
      }
    }


    private function count_suits_($cards) 
    {
      $this->number_of_suits = 0;

      foreach( $this->cards_by_suit as $suit => $cards ) 
      {
        if( $suit !== SUITS['WILD'] && count( $cards ) > 0 )
          $this->number_of_suits++;
      }
    }


    private function arrange_cards_by_id_($cards) 
    {
      $id_ = function($card) {
         $card['id']; 
      };

      return array_map($id_, $cards); 
    }


    private function is_all_wild_cards_()
    {
      return $this->suit_count_is_(0);
    }

    private function suit_count_is_($count) 
    {
      return $this->number_of_suits == $count;
    }


    private function could_be_wild_single_or_wild_bomb_() 
    {
      $this->possibles[] = $this->may_be_wild_single_or_wild_bomb_();
      
      return $this->possibles;
    }

    private function may_be_wild_single_or_wild_bomb_() 
    {
      $combo_value = $this->sum_wild_card_ranks_();

      if( $combo_value == 0 )
        throw new ImpossibleCombination("impossible bomb");

      $combo_type = $this->wild_count_is_(1) ? 'set' : 'bomb';
      
      if( $combo_type == 'bomb' )
        $combo_value = ($combo_value % 10 - 1);

      return 
        array( 'type' => $combo_type
             , 'value' => $combo_value
             , 'serienbr' => $this->number_of_suits
             , 'nbr' => $this->number_of_cards
             , 'display' => $this->default_display 
             );
    }

    private function sum_wild_card_ranks_()
    {
      extract(WILD_CARDS);
      $wilds = $this->cards_by_suit[SUITS['WILD']];
      
      $JACK = isset($wilds[$JACK]) ? $JACK  : 0;
      
      $QUEEN = isset($wilds[$QUEEN]) ? $QUEEN : 0;
      
      $KING  = isset($wilds[$KING]) ? $KING  : 0;

      return $JACK + $QUEEN + $KING; // {0,11,12,13,23,24,25,36}
    }

    private function wild_count_is_($count) 
    {
      return $this->number_of_wilds_available == $count;
    }


    private function could_be_number_bomb_() 
    {
      return $this->has_only_one_(3) 
          && $this->has_only_one_(5)
          && $this->has_only_one_(7) 
          && $this->has_only_one_(9)
          && $this->has_4_spot_cards_in_same_or_mixed_suits_();
    }

    private function has_only_one_($of_this_rank) 
    {
      return count($this->cards_by_rank[ $of_this_rank ]) == 1;
    }

    private function has_4_spot_cards_in_same_or_mixed_suits_() 
    {
      return $this->card_count_is_(4) 
          && $this->wild_count_is_(0)
          && $this->suit_count_is_(1) 
          || $this->suit_count_is_(4);
    }

    private function card_count_is_($count) 
    {
      return $this->number_of_cards == $count;
    }


    private function could_be_rainbow_or_suited_bomb_() 
    {
      $this->possibles[] = $this->may_be_rainbow_or_suited_bomb_();

      return $this->possibles;
    }

    private function may_be_rainbow_or_suited_bomb_() 
    {
      list($RAINBOW_BOMB, $SUITED_BOMB) = array(1,6);
      $bomb_value = $this->suit_count_is_(4) ? $RAINBOW_BOMB : $SUITED_BOMB;
    
      return 
        array( 'type' => 'bomb'
             , 'value' => $bomb_value
             , 'serienbr' => $this->number_of_suits
             , 'nbr' => $this->number_of_cards
             , 'display' => $this->default_display 
             );
    }


    private function could_be_set_or_sequence_()
    {
      list($highest_rank, $lowest_rank) = $this->find_highest_and_lowest_ranks_();
      
      if( $lowest_rank == $highest_rank )
        $this->possibles[] = $this->may_be_set_of_value_($lowest_rank);

      // a sequence is two or more consecutively ranked sets, e.g., 6-6-6-7-7-7,
      // and this particular sequence would have a length of 2 (consectuve ranks),
      // a width of 3 (size of sets), and a rank of 7 (highest non-wild rank)
      for($width = 1; $width < count(SUITS); $width++)
      {
        $maybe_sequence = $this->may_be_sequence_of_width_($width);
        
        if( !empty($maybe_sequence) ) 
          $this->possibles[] = $maybe_sequence;
      }

      return $this->possibles;
    }

    private function find_highest_and_lowest_ranks_() 
    {
      $highest_rank = null;
      $lowest_rank  = null;

      foreach( $this->cards_by_suit as $suit => $cards_in_this_suit ) 
      {
        if( $suit !== SUITS['WILD'] && count($cards_in_this_suit) > 0 ) 
        {
          foreach( $cards_in_this_suit as $rank => $dummy ) 
          {
            $highest_rank = max( $rank, $highest_rank );
            $lowest_rank  = ($lowest_rank == null) ? $rank: min($rank, $lowest_rank);
          }
        }
      }

      $this->lowest_rank = $lowest_rank;
      return array($highest_rank, $lowest_rank);
    }

    private function may_be_set_of_value_($set_value) 
    {
      return array( 'type'=>'set', 'value'=>$set_value, 'serienbr'=>$this->number_of_suits,
                    'nbr'=>$this->number_of_cards, 'display'=>$this->default_display );
    }

    private function may_be_sequence_of_width_($width)
    {
      $length = floor( $this->number_of_cards / $width );
      $rank   = $this->lowest_rank + $length - 1;

      return
        $this->could_be_sequence_with_dimensions_($length, $width, $rank)
          ? $this->may_be_sequence_with_dimensions_($width, $rank)
          : array();
    }

    private function could_be_sequence_with_dimensions_($length, $width, $rank) 
    {
      return $this->has_enough_cards_for_sequence_of_width_($width)
          && $this->card_count_is_($length * $width)
          && $this->has_enough_suits_to_cover_sequence_width_($width)
          && $this->can_form_sequence_with_dimensions_($rank, $width);
    }

    private function has_enough_cards_for_sequence_of_width_($width) 
    {
      return ($width == 1 && $this->number_of_cards >= 3)
          || ($width > 1 && $this->number_of_cards >= $width * 2 );
    }

    private function has_enough_suits_to_cover_sequence_width_($width) 
    { // Note: in case of "-1", one of the sets in the sequence is "wild card only"
      return $this->suit_count_is_($width) || $this->suit_count_is_($width-1);
    }

    private function can_form_sequence_with_dimensions_($sequence_rank, $sequence_width) 
    {
      $number_of_wilds_used = 0;
      $this->card_display_order = array();

      for( $rank = $this->lowest_rank; $rank <= $sequence_rank; $rank++ ) 
      {
        $wild_card_ids = $this->wild_card_ids;
        $non_wildcards_played_with_this_rank = count( $this->cards_by_rank[ $rank ] );
        $number_of_wilds_used += $sequence_width - $non_wildcards_played_with_this_rank;

        foreach( $this->cards_by_rank[ $rank ] as $suit => $card_id )
          $this->card_display_order[] = $card_id;

        for( $i = $non_wildcards_played_with_this_rank; $i < $sequence_width; $i++ )
          $this->card_display_order[] = array_shift( $wild_card_ids );
      }

      return ($number_of_wilds_used == $this->number_of_wilds_available);
    }

    private function may_be_sequence_with_dimensions_($width, $rank) 
    {
      return 
        array( 'type' => 'sequence'
             , 'value' => $rank
             , 'serienbr' => $width
             , 'nbr' => $this->number_of_cards
             , 'display' => $this->card_display_order 
             );
    }


    // Check if there is a possibility to play a rainbow / uniform bomb among current set of cards
    function checkBombsAmongCards( $cards ) 
    { // REFACTOR: rename to 'detect_bombs'
      $inject_ = function($elem, $array) {
          return array_map(function ($n) use ($elem) { return array_merge((array)$elem, (array)$n); }, $array);
      };

      $zip_ = function($array1, $array2) use($inject_) {
          return array_reduce($array1, function ($v, $n) use ($array2, $inject_) { return array_merge($v, $inject_($n, $array2));  }, array());
      };

      $pluck_ = function($keys_for, $values_at) {
        return array_map( function($k) use($values_at) { return $values_at[$k]; }, $keys_for);
      };


      $this->group_cards_by_suit_and_rank_($cards); // we can use this to gather up all of the point cards then
      list($threes, $fives, $sevens, $nines) = array_map("array_keys", $pluck_( POINT_CARDS, $this->cards_by_rank ));
      $point_card_combos = $zip_($threes, $zip_($fives, $zip_($sevens, $nines))); // get all the combinations of the 4 point cards
      $maybe_bombs = array_map("array_unique", $point_card_combos);            // and finally squeeze out any duplicate suits

      $has_set_of_odd_cards_with_suit_count_ = function($n) use($maybe_bombs) {
        return count(array_filter($maybe_bombs, function($b) use($n) {return count($b) == $n;})) > 0;
      };
      
      return 
        array( 'rainbow' => $has_set_of_odd_cards_with_suit_count_(4)
             , 'suited' => $has_set_of_odd_cards_with_suit_count_(1)
             );

    }


  }// end class Combo

}
?>