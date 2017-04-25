Feature: Identify bombs in hand
	
	Background:
		Given we have a combo analyzer 

	Scenario Outline: A freshly dealt hand of 14 cards (no wild cards)
		Given a card with suit <s1> and rank <r1>
		And a card with suit <s2> and rank <r2>
		And a card with suit <s3> and rank <r3>
		And a card with suit <s4> and rank <r4>
		And a card with suit <s5> and rank <r5>
		And a card with suit <s6> and rank <r6>
		And a card with suit <s7> and rank <r7>
		And a card with suit <s8> and rank <r8>
		And a card with suit <s9> and rank <r9>
		And a card with suit <s10> and rank <r10>
		And a card with suit <s11> and rank <r11>
		And a card with suit <s12> and rank <r12>
		And a card with suit <s13> and rank <r13>
		And a card with suit <s14> and rank <r14>
	When the analyzer looks for bombs
	Then a rainbow bomb will be found is <rainbow> 
		And a suited bomb will be found is <suited> 
	
	Examples:
	    |s1|r1|s2|r2|s3|r3|s4|r4|s5|r5|s6|r6|s7|r7|s8|r8|s9|r9|s10|r10|s11|r11|s12|r12|s13|r13|s14|r14|rainbow| suited|
			| 1| 3| 1| 5| 1| 7| 1| 9| 2| 5| 3| 7| 4| 9| 2| 3| 3| 5| 4 | 7 | 2 | 9 | 4 | 5 | 4 | 3 | 3 | 9 | 'true'| 'true'|
			| 1| 2| 1| 4| 1| 6| 1| 8| 2| 2| 3| 4| 4| 8| 2|10| 3|10| 4 | 10| 2 | 8 | 4 | 6 | 4 | 3 | 3 | 9 |'false'|'false'|
			| 1| 3| 2| 5| 3| 7| 4| 9| 1| 2| 1| 4| 1| 6| 1| 8| 2| 2| 3 | 4 | 4 | 8 | 2 |10 | 3 |10 | 4 | 10| 'true'|'false'|
			| 1| 3| 1| 5| 1| 7| 1| 9| 1| 2| 1| 4| 1| 6| 1| 8| 2| 2| 3 | 4 | 4 | 8 | 2 |10 | 3 |10 | 4 | 10|'false'| 'true'|
			| 1| 3| 1| 5| 1| 7| 1| 9| 2| 3| 2| 5| 2| 7| 2| 9| 3| 3| 3 | 5 | 3 | 7 | 3 | 9 | 4 | 2 | 4 | 10|'false'| 'true'|
			 
