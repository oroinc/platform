@ticket-BAP-17313
@regression

Feature: Entity Fields on Datagrid
  In order to ensure that entity fields columns are loaded properly when enabled after being disabled
  As an Administator
  I need to disable entity field column, then enable and check that it is loaded

  Scenario: Create Custom Entity Field
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    And I click "Create Field"
    And I fill form with:
      | Field Name   | custom_select |
      | Storage Type | Table column  |
      | Type         | Select        |
    And I click "Continue"
    And set Options with:
      | Label               |
      | CustomSelectOption1 |
      | CustomSelectOption2 |
    And I fill form with:
      | Label                | Custom Select          |
      | Add To Grid Settings | Yes and do not display |
    And I save and close form
    And I should see "Field saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Ensure custom field column on datagrid can be loaded when enabled
    Given I go to System/User Management/Users
    And I shouldn't see "Custom Select" column in grid
    And click Edit admin in grid
    And I fill form with:
      | Custom Select | CustomSelectOption1 |
    And I save and close form
    And I should see "User saved" flash message
    And I go to System/User Management/Users
    When I show column Custom Select in grid
    Then I should see that "CustomSelectOption1" is in 1 row
