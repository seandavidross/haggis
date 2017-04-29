<?php

require_once "./lib/haggistwo.cards.php";

use Haggis\Cards\Card as Card;
use Haggis\Exception\InvalidSuit as InvalidSuit;
use Haggis\Exception\InvalidRank as InvalidRank;
use Haggis\Exception\InvalidOwner as InvalidOwner;
use Haggis\Exception\InvalidLocation as InvalidLocation;
use Haggis\Exception\InvalidPosition as InvalidPosition;


describe("Card", function() {

  describe("::__construct", function() {

    it("should fail with an invalid suit", function() {
      
      $creating_a_card_with_an_invalid_suit = function() {

        return new Card(8, Haggis\Cards\RANKS['8']);

      };

      expect($creating_a_card_with_an_invalid_suit)->toThrow(new InvalidSuit());

    });


    it("should fail with a null suit", function() {
      
      $creating_a_card_with_a_null_suit = function() {

        return new Card(null, Haggis\Cards\RANKS['8']);

      };

      expect($creating_a_card_with_a_null_suit)->toThrow(new TypeError());

    });


    it("should fail with an invalid rank", function() {
      
      $creating_a_card_with_an_invalid_rank = function() {

        return new Card(Haggis\Cards\SUITS['RED'], 15);

      };

      expect($creating_a_card_with_an_invalid_rank)->toThrow(new InvalidRank());

    });


    it("should fail with an invalid owner", function() {
      
      $creating_a_card_with_an_invalid_owner = function() {
        
        return 
          new Card(Haggis\Cards\SUITS['RED']
                  ,Haggis\Cards\RANKS['8']
                  ,array('owner' => 4)
                  );
      
      };

      expect($creating_a_card_with_an_invalid_owner)->toThrow(new InvalidOwner());

    });


    it("should fail with an invalid location", function() {
      
      $creating_a_card_with_an_invalid_location = function() {
      
        return 
          new Card(Haggis\Cards\SUITS['RED']
                  ,Haggis\Cards\RANKS['8']
                  ,array('location' => 4)
                  );
      
      };

      expect($creating_a_card_with_an_invalid_location)->toThrow(new InvalidLocation());

    });


    it("should fail with an invalid position", function() {
      
      $creating_a_card_with_an_invalid_position = function() {
      
        return 
          new Card(Haggis\Cards\SUITS['RED']
                  ,Haggis\Cards\RANKS['8']
                  ,array('position' => Haggis\Cards\MAX_HAND_SIZE + 1)
                  );
      
      };

      expect($creating_a_card_with_an_invalid_position)->toThrow(new InvalidPosition());

    });


    it("should fail with a negative position", function() {
      
      $creating_a_card_with_an_negative_position = function() {
      
        return 
          new Card(Haggis\Cards\SUITS['RED']
                  ,Haggis\Cards\RANKS['8']
                  ,array('position' => -1)
                  );
      
      };

      expect($creating_a_card_with_an_negative_position)->toThrow(new InvalidPosition());

    });

  });

});

?>