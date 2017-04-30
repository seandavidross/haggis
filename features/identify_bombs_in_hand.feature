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
      | s1       | r1 | s2       | r2  | s3       | r3  | s4       | r4  | s5       | r5  | s6       | r6  | s7       | r7  | s8       | r8  | s9       |r9   | s10     | r10 | s11      | r11 | s12      | r12 | s13     | r13 | s14     | r14 | rainbow | suited  |
      | 'ORANGE' | '3'| 'ORANGE' | '5' | 'ORANGE' | '7' | 'ORANGE' | '9' | 'YELLOW' | '5' | 'GREEN'  | '7' | 'BLUE'   | '9' | 'YELLOW' | '3' | 'GREEN'  | '5' | 'BLUE'  | '7' | 'YELLOW' | '9' | 'BLUE'   | '5' | 'BLUE'  | '3' | 'GREEN' | '9' | 'true'  | 'true'  |
      | 'ORANGE' | '2'| 'ORANGE' | '4' | 'ORANGE' | '6' | 'ORANGE' | '8' | 'YELLOW' | '2' | 'GREEN'  | '4' | 'BLUE'   | '8' | 'YELLOW' | 'T' | 'GREEN'  | 'T' | 'BLUE'  | 'T' | 'YELLOW' | '8' | 'BLUE'   | '6' | 'BLUE'  | '3' | 'GREEN' | '9' | 'false' | 'false' |
      | 'ORANGE' | '3'| 'YELLOW' | '5' | 'GREEN'  | '7' | 'BLUE'   | '9' | 'ORANGE' | '2' | 'ORANGE' | '4' | 'ORANGE' | '6' | 'ORANGE' | '8' | 'YELLOW' | '2' | 'GREEN' | '4' | 'BLUE'   | '8' | 'YELLOW' | 'T' | 'GREEN' | 'T' | 'BLUE'  | 'T' | 'true'  | 'false' |
      | 'ORANGE' | '3'| 'ORANGE' | '5' | 'ORANGE' | '7' | 'ORANGE' | '9' | 'ORANGE' | '2' | 'ORANGE' | '4' | 'ORANGE' | '6' | 'ORANGE' | '8' | 'YELLOW' | '2' | 'GREEN' | '4' | 'BLUE'   | '8' | 'YELLOW' | 'T' | 'GREEN' | 'T' | 'BLUE'  | 'T' | 'false' | 'true'  |
      | 'ORANGE' | '3'| 'ORANGE' | '5' | 'ORANGE' | '7' | 'ORANGE' | '9' | 'YELLOW' | '3' | 'YELLOW' | '5' | 'YELLOW' | '7' | 'YELLOW' | '9' | 'GREEN'  | '3' | 'GREEN' | '5' | 'GREEN'  | '7' | 'GREEN'  | '9' | 'BLUE'  | '2' | 'BLUE'  | 'T' | 'false' | 'true'  |
       
