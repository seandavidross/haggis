<?php

class Hand {
		
		private static function getActivePlayerId() {
			return 1; // just need a consistent value for testing
		}

		private function card_should_belong_to_you($card, $player_id) {
			if( $card['location'] != 'hand' || $card['location_arg'] != $player_id )
		      throw new feException( 'Card is not in your hand' );
		}

		private function combo_should_belong_to_you($cards, $player_id) {
			foreach( $cards as $card ) 
				$this->card_should_belong_to_you($card, $player_id);
		}

		private function arrange_cards_by_id($cards) {
			$get_id = function($card){ $card['id']; };
			return array_map($get_id, $cards); // sort?
		}

		// Analyse played combo
		// Return an array of possible combos = object of this kind:
		//  array( "type" => set/sequence/bomb,
		//         "value" => value of the combo (the higher, the better)
		//         "serienbr" => number of different serie
		//         "nbr" => number of cards
		//         "display" => cards ids in right order for display )
		//  ... or null if this is an invalid combo
		function analyzeCombo( $cards ) {
				// $this->combo_should_not_be_empty( $cards );
				$this->combo_should_belong_to_you( $cards, static::getActivePlayerId() );
				$default_display = $this->arrange_cards_by_id( $cards );

				$result = array();

		    // Build a "card grid" (serie-value and value-serie)
				// $grouped_by_suit = array_fill_keys( array(0,1,2,3,4,'wild'), array() );
		    $grid = array( 0 => array(), 1 => array(), 2 => array(), 3 => array(), 4 => array(), 'wild' => array() );
				// grouped_by_rank = array_fill( 2, 12, array() );
		    $gridvalue = array( 2=>array(),3=>array(),4=>array(),5=>array(),6=>array(),7=>array(),8=>array(),9=>array(),10=>array(),11=>array(),12=>array(),13=>array() );
		    $wild_cards_ids = array();
		    foreach( $cards as $card )
		    {
						// extract($card);
						// $grouped_by_suit[$suit][$rank] = $id;
		        $grid[ $card['type'] ][ $card['type_arg'] ] = $card['id']; // 'type' means 'suit', 'type_arg' means 'rank' 
						// $grouped_by_rank[$rank][$suit] = $id;
		        $gridvalue[ $card['type_arg'] ][ $card['type'] ] = $card['id'];
						// if( $suit == 'wild' ) $wild_card_ids[] = $id;
		        if( $card['type'] == 'wild' )
		            $wild_cards_ids[] = $card['id'];
		    }

		    // Number of series used
		    $nbr_cards = count( $cards );
		    $nbr_serie_used = 0;
		    $max_value = null;
		    $min_value = null;
		    foreach( $grid as $serie => $cards )
		    {
		        if( $serie !== 'wild' && count( $cards ) > 0 )
		        {
		            $nbr_serie_used ++;
		            foreach( $cards as $value => $dummy )
		            {
		                if( $max_value == null )
		                    $max_value = $value;
		                if( $min_value == null )
		                    $min_value = $value;
		                $max_value = max( $value, $max_value );
		                $min_value = min( $value, $min_value );
		            }
		        }
		    }

		    if( $nbr_serie_used == 0 ) // $number_of_suits == 0
		    {
		        // Only wild cards => must be a bomb with wild cards or a single wild card
		        if( count( $grid['wild'] ) == 1 )
		        {
		            // This is a single set
		            $set_value = null;
		            if( isset( $grid['wild'][11] ) )  // J
		                $set_value = 11;
		            else if( isset( $grid['wild'][12] ) )  // Q
		                $set_value = 12;
		            else if( isset( $grid['wild'][13] ) )  // K
		                $set_value = 13;

		            $result[] = array( 'type' => 'set', 'value' => $set_value, 'serienbr' => $nbr_serie_used, 'nbr' => $nbr_cards, 'display' => $default_display );
		        }
		        else
		        {
		            // This should be a bomb (2 to 3 wild cards)
		            $bomb_value = null; // $this->calculate_bomb_value();
		            if( isset( $grid['wild'][11] ) && isset( $grid['wild'][12] ) && isset( $grid['wild'][13] ) )  // JQK
		                $bomb_value = 5;
		            else if( isset( $grid['wild'][11] ) && isset( $grid['wild'][12] ) )  // JQ
		                $bomb_value = 2;
		            else if( isset( $grid['wild'][11] ) && isset( $grid['wild'][13] ) )  // JK
		                $bomb_value = 3;
		            else if( isset( $grid['wild'][12] ) && isset( $grid['wild'][13] ) )  // QK
		                $bomb_value = 4;
		            else
		                throw new feException("impossible bomb");

		            if( $bomb_value !== null ) // REMOVE: it can not be null: it is either 2,3,4,5 or an exception is thrown
		                $result[] = array( 'type' => 'bomb', 'value' => $bomb_value, 'serienbr' => $nbr_serie_used, 'nbr' => $nbr_cards, 'display' => $default_display );
		        }
		    }
		    else
		    {
		        // There is at least one non wild card
		        if( $min_value == $max_value )
		        {
		            // Non wild cards are all with the same value => this is a set
		            $set_value = $min_value;
		            $result[] = array( 'type' => 'set', 'value' => $set_value, 'serienbr' => $nbr_serie_used, 'nbr' => $nbr_cards, 'display' => $default_display );
		        }

		        if( $nbr_cards == 4 && count( $grid['wild'] ) == 0 && ( $nbr_serie_used == 1 || $nbr_serie_used == 4 ) )
		        {
		            // 4 cards and no wild cards ... this could be a bomb !
		            if( count( $gridvalue[ 3 ] )==1 && count( $gridvalue[ 5 ] )==1
		             && count( $gridvalue[ 7 ] )==1 && count( $gridvalue[ 9 ] )==1 )
		            {
		                // This is a bomb !
		                $display = $default_display;
		                if( $nbr_serie_used == 1 )
		                    $bomb_value = 6;    // 3 5 7 9 same color
		                else if( $nbr_serie_used == 4 )
		                {
		                    $bomb_value = 1;    // 3 5 7 9 different colors
		                    $display = array(
		                        reset( $gridvalue[ 3 ] ), reset( $gridvalue[ 5 ] ), reset( $gridvalue[ 7 ] ), reset( $gridvalue[ 9 ] )
		                    );
		                }

		                $result[] = array( 'type' => 'bomb', 'value' => $bomb_value, 'serienbr' => $nbr_serie_used, 'nbr' => $nbr_cards, 'display' => $display );
		            }
		        }

		        // From this point, we check sequences, sequence number by sequence number
		        $original_wild_card_ids = $wild_cards_ids;
		        for( $sequence_nbr = 1; $sequence_nbr <= 5; $sequence_nbr++ )
		        {
		            // Check there is an enough global number of cards
		            $displayed = array();
		            $wild_cards_ids = $original_wild_card_ids;
		            $bEnoughCards = false;
		            if( $sequence_nbr == 1 )
		                $bEnoughCards = ($nbr_cards >= 3 );    // At least 3 cards
		            else
		                $bEnoughCards = ($nbr_cards >= $sequence_nbr*2 );    // At least 2 cards per sequence

		            if( $bEnoughCards )
		            {
		                $sequenceLength = floor( $nbr_cards / $sequence_nbr );
		                if( $sequenceLength * $sequence_nbr == $nbr_cards ) // Check there is exactly required number of cards
		                {
		                    $sequenceFrom = $min_value;
		                    $sequenceTo = $sequenceFrom+$sequenceLength-1;

		                    if( $nbr_serie_used == $sequence_nbr || ($nbr_serie_used+1) == $sequence_nbr )
		                    {
		                        // Note: in case of "+1", one of the sequence is "wild card only"
		                        $nbr_wild_used = 0;
		                        $nbr_wild_available = count( $grid['wild'] );
		                        for( $value = $sequenceFrom; $value<=$sequenceTo; $value ++ )
		                        {
		                            $wild_used_for_this_value = 0;

		                            if( $value > 10 )
		                            {
		                                // It's all wild cards
		                                $wild_used_for_this_value = $sequence_nbr;
		                                $nbr_wild_used += $wild_used_for_this_value;
		                                for( $i=0; $i<$sequence_nbr; $i++ )
		                                {
		                                    $displayed[] = array_shift( $wild_cards_ids );
		                                }
		                            }
		                            else
		                            {
		                                $nbr_card_played_with_value = count( $gridvalue[ $value ] );
		                                foreach( $gridvalue[ $value ] as $serie_id => $card_id )
		                                {
		                                    $displayed[] = $card_id;
		                                }

		                                if( $nbr_card_played_with_value < $sequence_nbr )
		                                {
		                                    $wild_used_for_this_value = ($sequence_nbr - $nbr_card_played_with_value);
		                                    $nbr_wild_used += $wild_used_for_this_value;
		                                    for( $i=$nbr_card_played_with_value; $i<$sequence_nbr; $i++ )
		                                    {
		                                        $displayed[] = array_shift( $wild_cards_ids );
		                                    }
		                                }
		                            }
		                        }

		                        if( $nbr_wild_used == $nbr_wild_available )
		                            $result[] = array( 'type' => 'sequence', 'value' => $sequenceTo, 'serienbr' => $sequence_nbr, 'nbr' => $nbr_cards, 'display' => $displayed );
		                    }
		                }

		            }
		        }
		    }
		    return $result;
		}
}

?>