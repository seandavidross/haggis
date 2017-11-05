<?php

require_once "./lib/haggistwo.combo.php";
require_once "./lib/haggistwo.exceptions.php";

// the cards we'll be using to form combos...
require_once "./fixtures/haggis.fixtures.cards.php";

use Haggis\Cards\Combo as Combo;
use Haggis\Exception\NullCombination as NullCombination;
use Haggis\Exception\EmptyCombination as EmptyCombination;


describe("Combo", function() {

  describe("::__construct", function() {
  
    it("should fail when cards is null", function() {
        $creating_a_null_combo = function() {
          $combo = new Combo(null);
        };

        expect($creating_a_null_combo)
          ->toThrow(new TypeError());
    });


    it("should fail when cards are empty", function() {
        $creating_an_empty_combo = function() {
          $combo = new Combo(array());
        };

        expect($creating_an_empty_combo)
          ->toThrow(new EmptyCombination("A combo cannot contain zero cards"));
    });

    it("should fail when any card is null", function() {
        $creating_a_combo_with_a_null_card = function() {
          $combo = new Combo(array(null));
        };

        expect($creating_a_combo_with_a_null_card)
          ->toThrow(new NullCombination("A combo's cards cannot be null."));
    });
  
  });



  describe("#get_possible_combinations", function() {

    context("with a single spot card", function() {
      
      beforeEach(function() {
        $single = array( $GLOBALS['RED_FIVE']->to_hash() );

        $this->combo = new Combo($single);

        // until we remove $cards from method signature, we still need to pass it...
        $this->possibles = 
          $this
            ->combo
            ->get_possible_combinations();
      });

      
      it("should return only one possibility", function() {
        expect(count($this->possibles))->toBe(1); 
      });


      it("should be a set", function() {
        expect($this->possibles[0]['type'])->toBe('set');
      });

      
      it("should be a singleton", function() {
        expect($this->possibles[0]['nbr'])->toBe(1);
      });

    });


    context("with a single wild card", function() {
      
      beforeEach(function() {
        $single = array( $GLOBALS['WILD_KING']->to_hash() );

        $this->combo = new Combo($single);

        $this->possibles = 
          $this
            ->combo
            ->get_possible_combinations();
      });

      
      it("should return only one possibility", function() {
        expect(count($this->possibles))->toBe(1); 
      });


      it("should be a set", function() {
        expect($this->possibles[0]['type'])->toBe('set');
      });

      
      it("should be a singleton", function() {
        expect($this->possibles[0]['nbr'])->toBe(1);
      });

    });


    context("with two unmatched spot cards", function() {

      beforeEach(function() {
        $unmatched_cards = 
          array( $GLOBALS['RED_FIVE']->to_hash()
               , $GLOBALS['RED_SIX']->to_hash()
               );
        
        $this->combo = new Combo($unmatched_cards);

        $this->possibles = 
          $this
            ->combo
            ->get_possible_combinations();
      });

      
      it("should return no possibilities", function() {
        expect(count($this->possibles))->toBe(0);
      });

    });


    context("with one spot and one wild card", function() {

      beforeEach(function() {
        $one_spot_and_one_wild = 
          array( $GLOBALS['RED_FIVE']->to_hash()
               , $GLOBALS['WILD_KING']->to_hash()
               );
        
        $this->combo = new Combo($one_spot_and_one_wild);

        $this->possibles = 
          $this
            ->combo
            ->get_possible_combinations();
      });

      
      it("should return only one possibility", function() {
        expect(count($this->possibles))->toBe(1); 
      });


      it("should be a set", function() {
        expect($this->possibles[0]['type'])->toBe('set');
      });

      
      it("should be a pair", function() {
        expect($this->possibles[0]['nbr'])->toBe(2);
      });


    });


    context("with two matched spot cards", function() {

      beforeEach(function() {
        $two_matched_cards = 
          array( $GLOBALS['RED_FIVE']->to_hash()
               , $GLOBALS['GREEN_FIVE']->to_hash()
               );
        
        $this->combo = new Combo($two_matched_cards);

        $this->possibles = 
          $this
            ->combo
            ->get_possible_combinations();
      });

      
      it("should return only one possibility", function() {
        expect(count($this->possibles))->toBe(1); 
      });


      it("should be a set", function() {
        expect($this->possibles[0]['type'])->toBe('set');
      });

      
      it("should be a pair", function() {
        expect($this->possibles[0]['nbr'])->toBe(2);
      });


    });


    context("with two wild cards", function() {

      beforeEach(function() {
        $two_wild_cards = 
          array( $GLOBALS['WILD_JACK']->to_hash()
               , $GLOBALS['WILD_QUEEN']->to_hash()
               );
        
        $this->combo = new Combo($two_wild_cards);

        $this->possibles = 
          $this
            ->combo
            ->get_possible_combinations();
      });

      
      it("should return only one possibility", function() {
        expect(count($this->possibles))->toBe(1); 
      });


      it("should be a bomb", function() {
        expect($this->possibles[0]['type'])->toBe('bomb');
      });

      
      it("should have both cards", function() {
        expect($this->possibles[0]['nbr'])->toBe(2);
      });


    });



    context("with three matched spot cards", function() {

      beforeEach(function() {
        $three_matched_cards = 
          array( $GLOBALS['RED_FIVE']->to_hash()
               , $GLOBALS['GREEN_FIVE']->to_hash()
               , $GLOBALS['BLUE_FIVE']->to_hash()
               );
        
        $this->combo = new Combo($three_matched_cards);

        $this->possibles = 
          $this
            ->combo
            ->get_possible_combinations();
      });

      
      it("should return only one possibility", function() {
        expect(count($this->possibles))->toBe(1); 
      });


      it("should be a set", function() {
        expect($this->possibles[0]['type'])->toBe('set');
      });

      
      it("should be a 3-of-a-kind", function() {
        expect($this->possibles[0]['nbr'])->toBe(3);
      });


    });

   
   
    context("with three wild cards", function() {

      beforeEach(function() {
        $three_wild_cards = 
          array( $GLOBALS['WILD_JACK']->to_hash()
               , $GLOBALS['WILD_QUEEN']->to_hash()
               , $GLOBALS['WILD_KING']->to_hash()
               );
        
        $this->combo = new Combo($three_wild_cards);

        $this->possibles = 
          $this
            ->combo
            ->get_possible_combinations();
      });

      
      it("should return only one possibility", function() {
        expect(count($this->possibles))->toBe(1); 
      });


      it("should be a bomb", function() {
        expect($this->possibles[0]['type'])->toBe('bomb');
      });

      
      it("should have all 3 cards", function() {
        expect($this->possibles[0]['nbr'])->toBe(3);
      });


    });



    context("with two matched spot cards and one wild", function() {

      beforeEach(function() {
        $two_matched_and_one_wild = 
          array( $GLOBALS['RED_FIVE']->to_hash()
               , $GLOBALS['GREEN_FIVE']->to_hash()
               , $GLOBALS['WILD_JACK']->to_hash()
               );
        
        $this->combo = new Combo($two_matched_and_one_wild);

        $this->possibles = 
          $this
            ->combo
            ->get_possible_combinations();
      });

      
      it("should return only one possibility", function() {
        expect(count($this->possibles))->toBe(1); 
      });


      it("should be a set", function() {
        expect($this->possibles[0]['type'])->toBe('set');
      });

      
      it("should be a 3-of-a-kind", function() {
        expect($this->possibles[0]['nbr'])->toBe(3);
      });


    });


    context("with one spot card and two wilds", function() {

      beforeEach(function() {
        $one_spot_and_two_wild = 
          array( $GLOBALS['RED_FIVE']->to_hash()
               , $GLOBALS['WILD_JACK']->to_hash()
               , $GLOBALS['WILD_QUEEN']->to_hash()
               );
        
        $this->combo = new Combo($one_spot_and_two_wild);

        $this->possibles = 
          $this
            ->combo
            ->get_possible_combinations();
      });

      
      it("should return two possibilities", function() {
        expect(count($this->possibles))->toBe(2); 
      });


      it("could be a set", function() {
        expect($this->possibles[0]['type'])->toBe('set');
      });

      
      it("would be a 3-of-a-kind", function() {
        expect($this->possibles[0]['nbr'])->toBe(3);
      });

      
      it("could be a sequence", function() {
        expect($this->possibles[1]['type'])->toBe('sequence');
      });

      
      it("would be a run of 3", function() {
        expect($this->possibles[1]['nbr'])->toBe(3);
      });

    });



    context("with two consecutive cards, suited, and one wild", function() {

      beforeEach(function() {
        $run_of_two_and_one_wild = 
          array( $GLOBALS['RED_FIVE']->to_hash()
               , $GLOBALS['RED_SIX']->to_hash()
               , $GLOBALS['WILD_JACK']->to_hash()
               );
        
        $this->combo = new Combo($run_of_two_and_one_wild);

        $this->possibles = 
          $this
            ->combo
            ->get_possible_combinations();
      });

      
      it("should return only one possibility", function() {
        expect(count($this->possibles))->toBe(1); 
      });


      it("should be a sequence", function() {
        expect($this->possibles[0]['type'])->toBe('sequence');
      });

      
      it("should be a run of 3", function() {
        expect($this->possibles[0]['nbr'])->toBe(3);
      });


    });



    context("with two consecutive cards, unsuited, and one wild", function() {

      beforeEach(function() {
        $two_in_a_row_and_one_wild = 
          array( $GLOBALS['RED_FIVE']->to_hash()
               , $GLOBALS['GREEN_SIX']->to_hash()
               , $GLOBALS['WILD_JACK']->to_hash()
               );
        
        $this->combo = new Combo($two_in_a_row_and_one_wild);

        $this->possibles = 
          $this
            ->combo
            ->get_possible_combinations();
      });

      
      it("should return no possibilities", function() {
        expect(count($this->possibles))->toBe(0); 
      });

    });
    


    context("with one spot card and three wilds", function() {

      beforeEach(function() {
        $one_spot_and_three_wild = 
          array( $GLOBALS['RED_FIVE']->to_hash()
               , $GLOBALS['WILD_JACK']->to_hash()
               , $GLOBALS['WILD_QUEEN']->to_hash()
               , $GLOBALS['WILD_KING']->to_hash()
               );
        
        $this->combo = new Combo($one_spot_and_three_wild);

        $this->possibles = 
          $this
            ->combo
            ->get_possible_combinations();
      });

      
      it("should return three possibilities", function() {
        expect(count($this->possibles))->toBe(3); 
      });


      it("could be a set", function() {
        expect($this->possibles[0]['type'])->toBe('set');
      });

      
      it("would be a 4-of-a-kind", function() {
        expect($this->possibles[0]['nbr'])->toBe(4);
      });

      
      it("could be a sequence of singles", function() {
        expect($this->possibles[1]['type'])->toBe('sequence');
      });

      
      it("would be a run of 4", function() {
        expect($this->possibles[1]['nbr'])->toBe(4);
      });


      it("could be a sequence of pairs", function() {
        expect($this->possibles[2]['type'])->toBe('sequence');
      });

      
      it("would be a run of 2", function() {
        $number_of_cards_in_run_of_pairs = 4;

        expect($this->possibles[2]['nbr'])
          ->toBe($number_of_cards_in_run_of_pairs);
      });

    });



    context("with all odd, suited spot cards", function() {

      beforeEach(function() {
        $odd_suited_spot_cards = 
          array( $GLOBALS['RED_THREE']->to_hash()
               , $GLOBALS['RED_FIVE']->to_hash()
               , $GLOBALS['RED_SEVEN']->to_hash()
               , $GLOBALS['RED_NINE']->to_hash()
               );
        
        $this->combo = new Combo($odd_suited_spot_cards);

        $this->possibles = 
          $this
            ->combo
            ->get_possible_combinations();
      });

      
      it("should return only one possibility", function() {
        expect(count($this->possibles))->toBe(1); 
      });


      it("should be a bomb", function() {
        expect($this->possibles[0]['type'])->toBe('bomb');
      });

    });



    context("with all odd, mixed suit spot cards", function() {

      beforeEach(function() {
        $odd_mixed_suit_spot_cards = 
          array( $GLOBALS['RED_THREE']->to_hash()
               , $GLOBALS['GREEN_FIVE']->to_hash()
               , $GLOBALS['BLUE_SEVEN']->to_hash()
               , $GLOBALS['ORANGE_NINE']->to_hash()
               );
        
        $this->combo = new Combo($odd_mixed_suit_spot_cards);

        $this->possibles = 
          $this
            ->combo
            ->get_possible_combinations();
      });

      
      it("should return only one possibility", function() {
        expect(count($this->possibles))->toBe(1); 
      });


      it("should be a bomb", function() {
        expect($this->possibles[0]['type'])->toBe('bomb');
      });

    });



    context("with all odd, mixed suit spot cards, plus one", function() {

      beforeEach(function() {
        $odd_mixed_suit_spot_cards_plus_one = 
          array( $GLOBALS['RED_THREE']->to_hash()
               , $GLOBALS['GREEN_FIVE']->to_hash()
               , $GLOBALS['BLUE_FIVE']->to_hash()
               , $GLOBALS['BLUE_SEVEN']->to_hash()
               , $GLOBALS['ORANGE_NINE']->to_hash()
               );
        
        $this->combo = new Combo($odd_mixed_suit_spot_cards_plus_one);

        $this->possibles = 
          $this
            ->combo
            ->get_possible_combinations();
      });

      
      it("should return no possibilities", function() {
        expect(count($this->possibles))->toBe(0); 
      });

    });


  });


});

// REFACTOR: There's a lot of duplication.
// Q: How do I make helper methods in Kahlan?

?>