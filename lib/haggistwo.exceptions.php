<?php

namespace Haggis\Exception 
{
  use Exception;

  class ImpossibleCombination extends Exception {}

  class CardNotInHand extends Exception {}

  class NullCombination extends Exception {}

  class EmptyCombination extends Exception {}

  class InvalidSuit extends Exception {}
}

?>