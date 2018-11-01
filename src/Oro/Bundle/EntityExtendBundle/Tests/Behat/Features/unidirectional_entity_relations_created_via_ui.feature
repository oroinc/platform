@ticket-BAP-13725
Feature: Unidirectional entity relations created via UI
  In order to create custom relation between entities
  As an Administrator
  I want to have possibility to create unidirectional relations between entities

#  AC:
#  Administrator should be able to create both kinds of relations (uni- and bidirectional)
#  After save ‘bidirectional’ field should not be editable (like Target Entity field)
#  OneToMany relation should be always bidirectional

  Scenario: Create Many to Many relation field to not Extended entity
    Given I login as administrator
    And I go to System/ Entities/ Entity Management
    And filter Name as is equal to "User"
    And I click view User in grid
    And click "Create field"
    And I fill form with:
      |Field name  |State_m2m   |
      |Storage type|Table column|
      |Type        |Many to many|
    And click "Continue"
    When I fill form with:
      |Target entity             |Language|
    Then "Bidirectional" was set to "No" and is not editable
    And I fill form with:
      |Related entity Data Fields|Code |
      |Related entity Info Title |Id   |
      |Related entity Detailed   |Code |
    When I save and close form
    Then I should see "Field saved" flash message

  Scenario: Create One to Many relation field to Extended entity
    And I go to System/ Entities/ Entity Management
    And filter Name as is equal to "User"
    And I click view User in grid
    And click "Create field"
    And I fill form with:
      |Field name  |Related_product_o2m|
      |Storage type|Table column       |
      |Type        |One to many        |
    And click "Continue"
    Then I should not see "Language" entity for "Target Entity" select
    When I fill form with:
      |Target entity             |Business Unit|
    Then "Bidirectional" was set to "Yes" and is not editable
    And I fill form with:
      |Related entity data fields|Id   |
      |Related entity info title |Email|
      |Related entity detailed   |Fax  |
    When I save and close form
    Then I should see "Field saved" flash message

  Scenario: Create Many to One relation field to Extended entity
    And I go to System/ Entities/ Entity Management
    And filter Name as is equal to "User"
    And I click view User in grid
    And click "Create field"
    And I fill form with:
      |Field name  |Compatible_product_m2o|
      |Storage type|Table column          |
      |Type        |Many to one           |
    And click "Continue"
    When I fill form with:
      |Target entity|Organization|
      |Bidirectional|Yes         |
      |Target field |Name        |
    When I save and close form
    Then I should see "Field saved" flash message

  Scenario: Create Many to One relation field to not Extended entity
    And I go to System/ Entities/ Entity Management
    And filter Name as is equal to "User"
    And I click view User in grid
    And click "Create field"
    And I fill form with:
      |Field name  |not_extended_m2o|
      |Storage type|Table column    |
      |Type        |Many to one     |
    And click "Continue"
    When I fill form with:
      |Target entity|Language|
    Then "Bidirectional" was set to "No" and is not editable
    And I fill form with:
      |Target field |Code |
    When I save and close form
    Then I should see "Field saved" flash message

  Scenario: Create Many to Many relation field to Extended entity
    And I go to System/ Entities/ Entity Management
    And filter Name as is equal to "User"
    And I click view User in grid
    And click "Create field"
    And I fill form with:
      |Field name  |extended_m2m|
      |Storage type|Table column|
      |Type        |Many to many|
    And click "Continue"
    When I fill form with:
      |Target entity             |Organization|
      |Bidirectional             |Yes         |
      |Related entity data fields|Description |
      |Related entity info title |Id          |
      |Related entity detailed   |Name        |
    When I save and close form
    Then I should see "Field saved" flash message

  @ticket-BB-10400
  Scenario: Edit Many to One relation field to not Extended entity
    Given I go to System/ Entities/ Entity Management
      And filter Name as is equal to "Product"
      And I click view Product in grid
    When I click Edit "taxCode" in grid
      And I save and close form
    Then I should see "Field saved" flash message
