@not-automated
Feature: Password complexity feature
  In order to increase safety of users
  As Administrator
  I need to define complexity of user passwords

  Scenario: Set up user password complexity
    Given I login as "Administraror" user
    And I go to System/Configuration/User Settings
    And uncheck "Use default" for "Minimal Password Length" field
    And I fill in "Minimal Password Length" with "3"
    And I check "Require A Special Character"
    And I save setting
    And I go to System/User Management/Users
    And I start to create User
    Then I should have no possibility to create following users
    | Username   | Password  | Status         | Role                  |
    | mattjohnes | AAAA12345 | Enabled        | Sales Rep             |
    | joemann    | Joeman!!! | Disabled       | Leads Development Rep |
    | harrylee   | harrylee  | Enabled        | Marketing Manager     |
    | roothmio   | rooth1234 | Enabled        | Online Sales Rep      |
    | tonyjack   | 123       | Enabled        | Sales Manager         |
    | liltoxy    | LILTOXY   | Enabled        | Administrator         |
    | johndeer   | JohnDeer  | Enabled        | Administrator         |

  Scenario: Change user password complexity
    Given I login as "Administraror" user
    And I go to System/Configuration/User Settings
    And uncheck "Use default" for "Require A Special Character" field
    And uncheck "Use default" for "Require A Lowercase Letter" field
    And uncheck "Require A Lowercase Letter"
    And I check "Require A Special Character"
    And I save setting
    And I go to System/User Management/Users
    And I start to create User
    Then I should have no possibility to create following users
      | Username   | Password  | Status         | Role                  |
      | mattjohnes | AAAA12345 | Enabled        | Sales Rep             |
      | roothmio   | 1234***** | Enabled        | Online Sales Rep      |
      | johndeer   | JohnDeer1 | Enabled        | Administrator         |

    Scenario: Too short password error observing
      Given I login as "Administraror" user
      And I go to System/Configuration/User Settings
      And I start to create User
      And fill in "Password" with "Q1"
      Then I should see "The password must be at least 3 characters long and include an upper case letter and a special character" error message

  Scenario: Complexity password error observing
    Given I login as "Administraror" user
    And I go to System/Configuration/User Settings
    And I start to create User
    And fill in "Password" with "Q12"
    Then I should see "The password must include an upper case letter and a special character" error message
