<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Hook\Scope\AfterScenarioScope;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Assert\Functions;

require_once "./lib/haggistwo.cards.php";
require_once "./lib/haggistwo.combo.php";
require_once "./fixtures/haggis.fixtures.cards.php";


/**
 * Defines application features from the specific context.
 */
class FeatureContext extends TestCase implements Context, SnippetAcceptingContext
{
  // "type" => set/sequence/bomb,
  // "value" => value of the combination (the higher, the better)
  // "serienbr" => number of different serie
  // "nbr" => number of cards
  // "display" => cards ids in right order for display )

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct()
  {
    $this->combo = null;

    $this->cards = array();

    $this->possible_combinations = array();

    $this->possible_combination = array();
  }


  /**
  * @Given no cards were played
  */
  public function no_cards_were_played()
  {
    $this->cards = array();
  }


  /**
  * @Given a card that does not belong to me
  */
  public function a_card_that_does_not_belong_to_me()
  {
    $not_my_card = $this->create_card_('ORANGE', '2');

    $not_my_card['location_arg'] = 2; // I am at location_arg == 1

    $this->cards = array($not_my_card);
  }


  /**
  * @Then it should fail with error :error_message
  */
  public function it_should_fail_with_error($error_message)
  {
    $this->assertInstanceOf("\Exception", $this->possible_combinations );
    $this->assertEquals($this->possible_combinations->getMessage(), $error_message);
  }


  /**
  * @Given we have a combo analyzer
  */
  public function we_have_a_combo_analyzer()
  {
    //$this->combo = new Haggis\Cards\Combo(array($this->cards));//$GLOBALS['RED_FIVE']));
  }


  private function create_card_($suit, $rank)
  {
    return 
      array( 'id' => 1
           , 'location' => 'hand'
           , 'location_arg' => 1
           , 'type' => SUITS[$suit]
           , 'type_arg' => RANKS[$rank]
           );
  }


  /**
  * @When the analyzer is run
  */
  public function the_analyzer_is_run()
  {
    try
    {
      $this->combo = new Haggis\Cards\Combo($this->cards);
      
      $this->possible_combinations = $this->combo->get_possible_combinations();
    }
    catch( \Exception $error )
    {
      $this->possible_combinations = $error;
    }
  }


  /**
  * @Then there should be :number_of_valid_combos combo
  */
  public function there_should_be_this_many_combos($number_of_valid_combos)
  {
    $this->assertEquals(count($this->possible_combinations), $number_of_valid_combos);
    
    $this->possible_combination = array_shift($this->possible_combinations);
    
    array_unshift($this->possible_combinations, $this->possible_combination);
  }


  /**
  * @Then the combo type should be :type
  */
  public function the_combo_type_should_be( $type )
  {
    $type = ($type == 'null') ? null : $type;
    
    $combo_type 
      = (count($this->possible_combinations) > 1) 
      ? 'vague' 
      : $this->possible_combination['type'];
    
    $this->assertEquals( $combo_type, $type );
  }


  /**
  * @Then the number of cards should be :number_of_cards
  */
  public function the_number_of_cards_should_be( $number_of_cards )
  {
    $nbr = $number_of_cards == 'null' ? null : $number_of_cards;
    
    $this->assertEquals( $this->possible_combination['nbr'], $nbr );
  }


  /**
  * @Given a card with suit :first_suit and rank :first_rank
  */
  public function a_card_with_suit_and_rank($first_suit, $first_rank)
  {
    $this->cards[] = $this->create_card_($first_suit, $first_rank);
  }


  /**
  * @Then the display size should be :combo_size
  */
  public function the_display_size_should_be($combo_size)
  {
    $combo = $this->possible_combination['display'];
    $d = empty($combo) ? null : count($this->possible_combination['display']);
    
    $display_size = is_null($d) ? 'null' : $d;
    
    $this->assertEquals( $display_size, $combo_size );
  }


  /**
  * @AfterScenario
  */
  public function reset_all_cards(AfterScenarioScope $event)
  {
    $this->cards = array();
    
    $this->possible_combination = array();
    
    $this->possible_combinations = array();
  }


  /**
  * @When the analyzer looks for bombs
  */
  public function the_analyzer_looks_for_bombs()
  {
    $this->combo = new Haggis\Cards\Combo($this->cards);
    // REFACTOR: we want to remove the need to pass cards to #detect_bombs...
    $this->possible_combinations = $this->combo->detect_bombs();
  }


  /**
  * @Then a rainbow bomb will be found is :rainbow
  */
  public function a_rainbow_bomb_will_be_found_is($rainbow)
  {
    $found = $this->possible_combinations['rainbow'] ? 'true':'false';
    
    $this->assertEquals($found, $rainbow);
  }


  /**
  * @Then a suited bomb will be found is :suited
  */
  public function a_suited_bomb_will_be_found_is($suited)
  {
    $found = $this->possible_combinations['suited'] ? 'true':'false';
    
    $this->assertEquals($found, $suited);
  }

}

