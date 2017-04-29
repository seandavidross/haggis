<?php

require_once "./lib/haggistwo.cards.php";

use Haggis\Cards\Card as Card;
use Haggis\Exception\InvalidSuit as InvalidSuit;
use Haggis\Exception\InvalidRank as InvalidRank;


describe("Card", function() {

  describe("::__construct", function() {

    it("should fail with an invalid suit", function() {
      $creating_a_card_with_an_invalid_suit = function() {
        return new Card(8, Haggis\Cards\RANKS['8']);
      };

      expect($creating_a_card_with_an_invalid_suit)->toThrow(new InvalidSuit());

    });

    it("should fail with an invalid rank", function() {
      $creating_a_card_with_an_invalid_rank = function() {
        return new Card(Haggis\Cards\SUITS['RED'], 15);
      };

      expect($creating_a_card_with_an_invalid_rank)->toThrow(new InvalidRank());

    });


  });


});

?>