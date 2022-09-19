@regression
@ticket-BB-20696

Feature: Dependent menu item updated with global menu update
  In order to be able to manage menu items
  As an administrator
  I want to have update dependent menus when global menu updated

  Scenario: Create Global menu item
    Given I login as administrator
    When I go to System/Menus
    And I click view application_menu in grid
    And I click "Create Menu Item"
    When I fill "Menu Form" with:
      | Title       | Test Item        |
      | URI         | /about           |
      | Description | test description |
    And I save form
    Then I should see "Menu item saved successfully." flash message

  Scenario: Save menu item for User
    When I click My User in user menu
    And I follow "More actions"
    And I follow "Edit Menus"
    And I click view application_menu in grid
    And I should see "Select existing menu item or create new"
    And I click "Test Item"
    And I save form
    Then I should see "Menu item saved successfully." flash message

  Scenario: Update menu item on global level
    When I go to System/Menus
    And I click view application_menu in grid
    And I click "Test Item"
    When I fill "Menu Form" with:
      | URI | /about_updated |
    And I save form
    Then I should see "Menu item saved successfully." flash message

  Scenario: Check menu item for User
    When I click My User in user menu
    And I follow "More actions"
    And I follow "Edit Menus"
    And I click view application_menu in grid
    And I click "Test Item"
    Then URI field should has "/about_updated" value
