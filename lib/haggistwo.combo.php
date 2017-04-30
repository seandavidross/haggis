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
      $this->wild_cards_ids = array();
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

      $this->prepare_to_analyze($cards);
      list($highest_rank, $lowest_rank) = $this->find_highest_and_lowest_ranks();

      $this->possibles = array();

      if( $this->suit_count_is(0) ) // it's empty or it only has wild cards
      {
        $this->possibles[] = $this->may_be_wild_singleton_or_wild_bomb();
      }
      else if( $this->could_be_rainbow_or_suited_bomb() ) 
      {
        $this->possibles[] = $this->may_be_rainbow_or_suited_bomb();
      }
      else
      {
        if( $lowest_rank == $highest_rank )
          $this->possibles[] = $this->may_be_set_with_value($lowest_rank);

        // a sequence is two or more consecutively ranked sets, e.g., 6-6-6-7-7-7,
        // and this particular sequence would have a length of 2 (consectuve ranks),
        // a width of 3 (size of sets), and a rank of 7 (highest non-wild rank)
        for($width = 1; $width < count(SUITS); $width++)
        {
          $maybe_sequence = $this->may_be_sequence_of_width($width);
          
          if( !empty($maybe_sequence) ) 
            $this->possibles[] = $maybe_sequence;
        }
      } // end else

      $this->has_cached_possibles = true;

      return $this->possibles;
    } // end #get_possible_combinations

    function may_be_sequence_of_width($width)
    {
      $length = floor( $this->number_of_cards / $width );
      $rank   = $this->lowest_rank + $length - 1;

      return
        $this->could_be_sequence_with_dimensions($length, $width, $rank)
          ? $this->may_be_sequence_with_dimensions($width, $rank)
          : array();
    }

    private function prepare_to_analyze($cards) {
      // $this->combo_should_not_be_empty( $cards );
      // $this->combo_should_not_be_bigger_than(MAX_HAND_SIZE);
      $this->combo_should_belong_to_you($cards);

      $this->group_cards_by_suit_and_rank($cards);
      $this->count_suits($cards);
      $this->default_display = $this->arrange_cards_by_id( $cards );
      $this->number_of_cards = count($cards);
      $this->number_of_wilds_available = count( $this->cards_by_suit[SUITS['WILD']] );
    }

  // The following 3 methods probably belong in the controller class,
  // the class that will call #get_possible_combinations, this class shouldn't care
  // about who has the cards or where they came from, it only needs to
  // know if the cards form valid Haggis combinations or not...
    private function combo_should_belong_to_you($cards)
    {
      foreach( $cards as $card )
      {
        $this->card_should_belong_to_you($card);
      }
    }

    private function card_should_belong_to_you($card) {
      if( $card['location'] != 'hand' || $card['location_arg'] != static::getActivePlayerId() )
        throw new CardNotInHand('Card is not in your hand');
    }

    private static function getActivePlayerId() {
      return 1; // just need a consistent value for testing
    }
  // REFACTOR: move the above methods into HaggisTwo class.

    private function group_cards_by_suit_and_rank($cards) {
      // I'm using a couple of lambdas to alias some vague key names...
      $suit_of = function($c){ return $c['type']; };
      $rank_of = function($c){ return $c['type_arg']; };

      // Build a "card grid" (serie-value and value-serie)
      $this->cards_by_suit  = array_fill_keys( SUITS, array() );
      $this->cards_by_rank  = array_fill_keys( RANKS, array() );
      $this->wild_cards_ids = array();

      foreach( $cards as $card ) {
        $this->cards_by_suit[ $suit_of($card) ][ $rank_of($card) ] = $card['id'];
        $this->cards_by_rank[ $rank_of($card) ][ $suit_of($card) ] = $card['id'];
        if( $suit_of($card) == SUITS['WILD'] )
          $this->wild_cards_ids[] = $card['id'];
      }
    }

    private function count_suits($cards) {
      $this->number_of_suits = 0;

      foreach( $this->cards_by_suit as $suit => $cards ) {
        if( $suit !== SUITS['WILD'] && count( $cards ) > 0 )
          $this->number_of_suits++;
      }
    }

    private function arrange_cards_by_id($cards) {
      $pluck_id = function($c){ $c['id']; };
      return array_map($pluck_id, $cards); // should we sort the cards?
    }

    private function find_highest_and_lowest_ranks() {
      $highest_rank = null;
      $lowest_rank  = null;

      foreach( $this->cards_by_suit as $suit => $cards_in_this_suit ) {
        if( $suit !== SUITS['WILD'] && count($cards_in_this_suit) > 0 ) {
          foreach( $cards_in_this_suit as $rank => $dummy ) {
            $highest_rank = max( $rank, $highest_rank );
            $lowest_rank  = ($lowest_rank == null) ? $rank: min($rank, $lowest_rank);
          }
        }
      }

      $this->lowest_rank = $lowest_rank;
      return array($highest_rank, $lowest_rank);
    }

    private function suit_count_is($count) {
      return $this->number_of_suits == $count;
    }

    private function may_be_wild_singleton_or_wild_bomb() {
      extract(WILD_CARDS); // either combo has only wild cards or...
      $JACK  = isset( $this->cards_by_suit[SUITS['WILD']][$JACK] )  ? $JACK  : 0;
      $QUEEN = isset( $this->cards_by_suit[SUITS['WILD']][$QUEEN] ) ? $QUEEN : 0;
      $KING  = isset( $this->cards_by_suit[SUITS['WILD']][$KING] )  ? $KING  : 0;

      $combo_value = $JACK + $QUEEN + $KING; // {0,11,12,13,23,24,25,36}
      // ... there are no cards in the combo at all...
      if( $combo_value == 0 )
        throw new ImpossibleCombination("impossible bomb");

      $combo_type  = $this->wild_count_is(1) ? 'set' : 'bomb';
      $combo_value = $combo_type == 'bomb' ? ($combo_value % 10 - 1): $combo_value;

      return array( 'type'=>$combo_type, 'value'=>$combo_value,
                    'serienbr'=>$this->number_of_suits,
                    'nbr'=>$this->number_of_cards, 'display'=>$this->default_display );
    }

    private function could_be_rainbow_or_suited_bomb() {
      return $this->has_four_cards_in_one_suit_or_four_suits_with_no_wild_cards()
          && $this->has_only_one(3) && $this->has_only_one(5)
          && $this->has_only_one(7) && $this->has_only_one(9);
    }

    private function has_four_cards_in_one_suit_or_four_suits_with_no_wild_cards() {
      return $this->card_count_is(4) && $this->wild_count_is(0)
          && $this->suit_count_is(1) || $this->suit_count_is(4);
    }

    private function card_count_is($count) {
      return $this->number_of_cards == $count;
    }

    private function wild_count_is($count) {
      return $this->number_of_wilds_available == $count;
    }

    private function has_only_one($of_this_rank) {
      return count($this->cards_by_rank[ $of_this_rank ]) == 1;
    }

    private function may_be_rainbow_or_suited_bomb() {
      list($RAINBOW_BOMB, $SUITED_BOMB) = array(1,6);
      $bomb_value = $this->suit_count_is(4) ? $RAINBOW_BOMB : $SUITED_BOMB;
      return array( 'type'=>'bomb', 'value'=>$bomb_value, 'serienbr'=>$this->number_of_suits,
                    'nbr'=>$this->number_of_cards, 'display'=>$this->default_display );
    }

    private function may_be_set_with_value($set_value) {
      return array( 'type'=>'set', 'value'=>$set_value, 'serienbr'=>$this->number_of_suits,
                    'nbr'=>$this->number_of_cards, 'display'=>$this->default_display );
    }

    private function could_be_sequence_with_dimensions($length, $width, $rank) {
      return $this->has_enough_cards_to_form_a_sequence_of_width($width)
          && $this->card_count_is($length * $width)
          && $this->has_enough_suits_to_cover_sequence_width($width)
          && $this->can_form_a_sequence_with_these_dimensions($rank, $width);
    }

    private function has_enough_cards_to_form_a_sequence_of_width($width) {
      return ($width == 1 && $this->number_of_cards >= 3)
          || ($width > 1 && $this->number_of_cards >= $width*2 );
    }

    private function has_enough_suits_to_cover_sequence_width($width) {
      // Note: in case of "-1", one of the sets in the sequence is "wild card only"
      return $this->suit_count_is($width) || $this->suit_count_is($width-1);
    }

    private function can_form_a_sequence_with_these_dimensions($sequence_rank, $sequence_width) {
      $number_of_wilds_used = 0;
      $this->card_display_order = array();

      for( $rank = $this->lowest_rank; $rank <= $sequence_rank; $rank++ ) {
        $wild_card_ids = $this->wild_cards_ids;
        $non_wildcards_played_with_this_rank = count( $this->cards_by_rank[ $rank ] );
        $number_of_wilds_used += $sequence_width - $non_wildcards_played_with_this_rank;

        foreach( $this->cards_by_rank[ $rank ] as $suit => $card_id )
          $this->card_display_order[] = $card_id;

        for( $i = $non_wildcards_played_with_this_rank; $i < $sequence_width; $i++ )
          $this->card_display_order[] = array_shift( $wild_card_ids );
      }

      return ($number_of_wilds_used == $this->number_of_wilds_available);
    }

    private function may_be_sequence_with_dimensions($width, $rank) {
      return 
        array( 'type' => 'sequence'
             , 'value' => $rank
             , 'serienbr' => $width
             , 'nbr' => $this->number_of_cards
             , 'display' => $this->card_display_order 
             );
    }


    // Check if there is a possibility to play a rainbow / uniform bomb among current set of cards
    function checkBombsAmongCards( $cards ) { // REFACTOR: rename to 'detect_bombs'
      $inject = function($elem, $array) {
          return array_map(function ($n) use ($elem) { return array_merge((array)$elem, (array)$n); }, $array);
      };

      $zip = function($array1, $array2) use($inject) {
          return array_reduce($array1, function ($v, $n) use ($array2, $inject) { return array_merge($v, $inject($n, $array2));  }, array());
      };

      $pluck = function($keys_for, $values_at) {
        return array_map( function($k) use($values_at) { return $values_at[$k]; }, $keys_for);
      };


      $this->group_cards_by_suit_and_rank($cards); // we can use this to gather up all of the point cards then
      list($threes, $fives, $sevens, $nines) = array_map("array_keys", $pluck( POINT_CARDS, $this->cards_by_rank ));
      $point_card_combos = $zip($threes, $zip($fives, $zip($sevens, $nines))); // get all the combinations of the 4 point cards
      $maybe_bombs = array_map("array_unique", $point_card_combos);            // and finally squeeze out any duplicate suits

      $has_one_of_each_point_card_in_this_many_suits = function($n) use($maybe_bombs) {
        return count(array_filter($maybe_bombs, function($b) use($n) {return count($b) == $n;})) > 0;
      };
      // whatever remains, will be a bomb, if it has the correct number of suits...
      return array('rainbow'=>$has_one_of_each_point_card_in_this_many_suits(4),
                   'suited' =>$has_one_of_each_point_card_in_this_many_suits(1));

    }


  }// end class Combo

}
?>