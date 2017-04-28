<?php

require_once "./lib/haggistwo.cards.php";
require_once "./lib/haggistwo.combo.php";

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

        expect($creating_a_null_combo)->
          toThrow(new TypeError());
    });


    it("should fail when cards are empty", function() {
        $creating_an_empty_combo = function() {
          $combo = new Combo(array());
        };

        expect($creating_an_empty_combo)->
          toThrow(new EmptyCombination("A combo cannot contain zero cards"));
    });

    it("should fail when any card is null", function() {
        $creating_a_combo_with_a_null_card = function() {
          $combo = new Combo(array(null));
        };

        expect($creating_a_combo_with_a_null_card)->
          toThrow(new NullCombination("A combo's cards cannot be null."));
    });
  
  });



  describe("#get_possible_combinations", function() {

    context("with_a_single_card", function() {
      
      beforeEach(function() {
        $this->combo_ = new Combo( array($GLOBALS['RED_FIVE']) );
      });

      it("should return a set", function() {

      });

    });

  });


});

?>