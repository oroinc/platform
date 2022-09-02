@ticket-BAP-21510

Feature: Updated user menu item
  In order to be able to manage menu items
  As an administrator
  I want to have update dependent menus

  Scenario: Save menu item for User
    Given I login as administrator
    When I click My User in user menu
    And I follow "More actions"
    And I follow "Edit Menus"
    And I click view application_menu in grid
    Then I should see "Select existing menu item or create new"
    When I click "Create Menu Item"
    And I fill "Menu Form" with:
      | Title       | Test Item        |
      | URI         | /about           |
      | Description | test description |
    And I save form
    Then I should see "Menu item saved successfully." flash message

  Scenario: Create child menu item
    When I click "Create Menu Item"
    And I fill "Menu Form" with:
      | Title       | Test Item Child        |
      | URI         | /about_child           |
      | Description | test description child |
    And I save form
    Then I should see "Menu item saved successfully." flash message

  Scenario: Update menu item
    When I click "Test Item"
    And I fill "Menu Form" with:
      | Title | Test Item Update |
      | URI   | /about_updated   |
    And I save form
    Then I should see "Menu item saved successfully." flash message
    And page has "Test Item Update" header

  Scenario: Update child menu item
    When I click "Test Item Child"
    And I fill "Menu Form" with:
      | Title | Test Item Child Update |
      | URI   | /about_child_updated   |
    And I save form
    Then I should see "Menu item saved successfully." flash message
    And page has "Test Item Child Update" header
