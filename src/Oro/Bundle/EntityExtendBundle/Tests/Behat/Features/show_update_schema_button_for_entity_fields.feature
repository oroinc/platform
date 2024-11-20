@regression
@ticket-BAP-9337
@ticket-BAP-15577
@automatically-ticket-tagged
@waf-skip
Feature: Show "Update Schema" button for entity fields
  In order to find out that schema update for custom entity fields is required, and be able to do it
  As a Site Administrator
  I want to see "Update Schema" button at entity screen

  Scenario: View User Entity Management
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    Then click View User in grid

  Scenario: Create Custom Field
    Given I click "Create Field"
    When I fill form with:
      | Field name | some_field |
      | Type       | Decimal    |
    And I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message
    And I should see "Update Schema"

  Scenario: Create another one Custom Field
    Given I click "Create Field"
    When I fill form with:
      | Field name | some_another_field |
      | Type       | Boolean            |
    And I click "Continue"
    When I save and close form
    Then I should see "Field saved" flash message
    And I should see "Update Schema"

  Scenario: Update schema
    Given I click update schema
    And I should see Schema updated flash message
    And I move backward one page
    And I should not see "Update Schema"

  Scenario: Remove Custom Field
    Given I click Remove some_another_field in grid
    Then I should see "Delete Confirmation"
    And I click "Yes"
    Then I should see some_another_field Boolean some_another_field Custom Deleted in grid
    And I should see "Update Schema"

  Scenario: Create Custom Field (HTML TAGS)
    Given I click "Create Field"
    And I fill form with:
      | Field name | some_field_html |
      | Type       | Decimal         |
    And I click "Continue"
    And I fill form with:
      | Label       | Test Custom Field <script>alert(1)</script> |
      | Description | Description <script>alert(1)</script>       |
    And I save form
    Then I should see "Field saved" flash message
    And "OroForm" must contains values:
      | Label       | Test Custom Field alert(1) |
      | Description | Description alert(1)       |
    When I save and close form
    Then I should see "some_field_html"
    And I should see "Test Custom Field alert(1)"
