Feature: Identify played combo
  
  Background:
    Given we have a combo analyzer 

  # 'impossible combo' would be better than 'impossible bomb'
  # best would be 'Cannot play a combo with zero cards in it'
  Scenario: Zero cards are played
    Given no cards were played
    When the analyzer is run
    Then it should fail with error 'impossible bomb'
    
  Scenario: Card does not belong to me
    Given a card that does not belong to me
    When the analyzer is run
    Then it should fail with error 'Card is not in your hand'

  Scenario: A single card is played
    Given a card with suit '1' and rank '2'
    When the analyzer is run
    Then there should be '1' combo
      And the combo type should be "set"
      And the number of cards should be '1'
      And the display size should be '1'
      
      
  Scenario Outline: Two cards are played
    Given a card with suit <first_suit> and rank <first_rank>
      And a card with suit <second_suit> and rank <second_rank>
    When the analyzer is run
    Then there should be <valid_combos> combo
      And the combo type should be <combo_type>
      And the number of cards should be <combo_size>
      And the display size should be <combo_size>
    
    Examples:
        | first_suit  | first_rank  | second_suit | second_rank | valid_combos  | combo_type  | combo_size  |
        | 1           | 2           | 2           | 2           | 1             | 'set'       | 2           |
        | 'wild'      | 11          | 2           | 2           | 1             | 'set'       | 2           |
        | 'wild'      | 11          | 'wild'      | 12          | 1             | 'bomb'      | 2           | 
        | 1           | 2           | 2           | 3           | 0             | 'null'      | 'null'      |
        
  Scenario Outline: Three cards are played
  Given a card with suit <s1> and rank <r1>
    And a card with suit <s2> and rank <r2>
    And a card with suit <s3> and rank <r3>
  When the analyzer is run
  Then there should be <valid_combos> combo
    And the combo type should be <combo_type>
    And the number of cards should be <combo_size>
    And the display size should be <combo_size>

    Examples:
        | s1    | r1  | s2    | r2  | s3    | r3  | valid_combos  | combo_type        | combo_size  |
        | 1     | 2   | 2     | 2   | 3     | 2   | 1             | 'set'             | 3           |
        | 1     | 2   | 1     | 3   | 1     | 4   | 1             | 'sequence'        | 3           |
        |'wild' | 11  |'wild' | 12  |'wild' | 13  | 1             | 'bomb'            | 3           |
        |'wild' | 11  | 1     | 3   | 2     | 3   | 1             | 'set'             | 3           |
        |'wild' | 11  |'wild' | 12  | 1     | 3   | 2             | 'vague'           | 3           |
        |'wild' | 11  |'wild' | 12  |'wild' | 13  | 1             | 'bomb'            | 3           | 
        |'wild' | 11  | 1     | 2   | 2     | 3   | 0             | 'null'            | 'null'      |

  Scenario Outline: Four cards are played
  Given a card with suit <s1> and rank <r1>
    And a card with suit <s2> and rank <r2>
    And a card with suit <s3> and rank <r3>
    And a card with suit <s4> and rank <r4>
  When the analyzer is run
  Then there should be <valid_combos> combo
    And the combo type should be <combo_type>
    And the number of cards should be <combo_size>
    And the display size should be <combo_size>
    # 3rd & 4th examples are suited and rainbow bombs, respectively
    # the 2nd last example could be a set, a sequence, or a sequence of pairs.
  Examples:
        | s1    | r1  | s2    | r2  | s3    | r3  | s4  | r4  | valid_combos  | combo_type        | combo_size  |
        | 1     | 2   | 2     | 2   | 3     | 2   | 4   | 2   | 1             | 'set'             | 4           |
        | 1     | 2   | 1     | 3   | 1     | 4   | 1   | 5   | 1             | 'sequence'        | 4           |
        | 1     | 3   | 1     | 5   | 1     | 7   | 1   | 9   | 1             | 'bomb'            | 4           |
        | 1     | 3   | 2     | 5   | 3     | 7   | 4   | 9   | 1             | 'bomb'            | 4           |       
        |'wild' | 11  | 1     | 3   | 2     | 3   | 4   | 3   | 1             | 'set'             | 4           |
        |'wild' | 11  |'wild' | 12  | 1     | 3   | 3   | 3   | 2             | 'vague'           | 4           |
        |'wild' | 11  |'wild' | 12  |'wild' | 13  | 1   | 5   | 3             | 'vague'           | 4           | 
        |'wild' | 11  | 1     | 2   | 2     | 3   | 4   | 4   | 0             | 'null'            | 'null'      |
        | 1     | 2   | 2     | 2   | 1     | 3   | 2   | 3   | 1             | 'sequence'        | 4           |
        | 1     | 2   | 2     | 2   | 1     | 3   | 3   | 3   | 0             | 'null'            | 'null'      |       


    # As a characteristic test, determining how the existing code works, 
    # this scenario should succeed. As a specification test, determining
    # how the code *should* work, this scenario should fail...
    Scenario Outline: Too many cards are played
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
      And a card with suit <s15> and rank <r15>
    When the analyzer is run
    Then there should be <valid_combos> combo
      And the combo type should be <combo_type>
      And the number of cards should be <combo_size>
      And the display size should be <combo_size>
    
    Examples:
        | s1| r1| s2| r2| s3| r3| s4| r4| s5| r5| s6| r6| s7| r7| s8| r8| s9| r9| s10| r10| s11| r11| s12| r12| s13| r13| s14| r14| s15| r15|valid_combos|combo_type|combo_size |
        | 1 | 2 | 2 | 2 | 3 | 2 | 4 | 2 | 5 | 2 | 1 | 3 | 2 | 3 | 3 | 3 | 4 | 3 | 5  | 3  | 1  | 4  | 2  | 4  | 3  | 4  | 4  | 4  | 5  | 4  | 1          |'sequence'| 15        |
             
