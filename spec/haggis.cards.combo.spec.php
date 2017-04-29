<?php

require_once "./lib/haggistwo.cards.php";
require_once "./lib/haggistwo.combo.php";
require_once "./lib/haggistwo.exceptions.php";

use Haggis\Cards\Card as Card;
use Haggis\Cards\Combo as Combo;
use Haggis\Cards\Attributes;
use Haggis\Exception\NullCombination as NullCombination;
use Haggis\Exception\EmptyCombination as EmptyCombination;

const SUITS = Haggis\Cards\SUITS;
const RANKS = Haggis\Cards\RANKS;

global $RED_FIVE;
$RED_FIVE = new Card( SUITS['RED'], RANKS['5'] );


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

    context("with a single non-wild card", function() {
      
      beforeEach(function() {
        $singleton = $GLOBALS['RED_FIVE']->to_hash();

        $this->combo_ = new Combo( array($singleton) );

        // until we remove $cards from method signature, we still need to pass it...
        $this->possibles_ 
          = $this
              ->combo_
              ->get_possible_combinations( array($singleton) );
      });

      
      it("should return only one possibility", function() {
        expect(count($this->possibles_))->toBe(1); 
      });


      it("should return a set", function() {
        expect($this->possibles_[0]['type'])->toBe('set');
      });

      
      it("should be a singleton", function() {
        expect($this->possibles_[0]['nbr'])->toBe(1);
      });

    });

  });


});

?>