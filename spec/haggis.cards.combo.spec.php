<?php

require_once "./lib/haggistwo.cards.php";
use Haggis\Cards\Combo as Combo;
use Haggis\Exception\NullCombination as NullCombination;

describe("Combo", function() {

  describe("::__construct", function() {
  
    it("fails when cards are null", function() {
        $creating_a_null_combo = function() {
          $combo = new Combo(null);
        };

        expect($creating_a_null_combo)->toThrow(new NullCombination("A combo's cards cannot be null."));

    });
  
  });

});

?>