@ticket-BAP-20723
@regression

Feature: Entity field Date
  In order to have custom Date fields for an entity
  As an Administrator
  I need to be able to create Date field and manage field data

  Scenario: Prepare Date field for User entity
    Given I login as administrator
    When I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    And I click on "Create Field"
    And I fill form with:
      | Field name | DateField |
      | Type       | Date      |
    And I click "Continue"
    And I save and close form
    And click update schema
    Then I should see Schema updated flash message

  Scenario: Update User Date field
    When I go to System/User Management/Users
    And click Edit admin in grid
    And fill form with:
      | DateField | <Date:Jul 28, 2021> |
    And save and close form
    Then I should see "User saved" flash message
    And I should see User with:
      | Username  | admin        |
      | DateField | Jul 28, 2021 |
    When I go to System/User Management/Users
    Then I should see following grid:
      | Username | DateField     |
      | admin    | Jul 28, 2021 |
