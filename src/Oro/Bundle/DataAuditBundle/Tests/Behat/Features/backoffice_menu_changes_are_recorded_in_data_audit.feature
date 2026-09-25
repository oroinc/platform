@regression
@ticket-BB-27644

Feature: Back-office menu changes are recorded in Data Audit

  Scenario: Feature background
    Given I login as administrator

  Scenario: Changing a menu item is recorded as a change of that item
    Given I go to System/ Menus
    And I click view "application_menu" in grid
    When I click on "Products" in tree "Sidebar Menu Tree"
    And I fill "Menu Form" with:
      | Title | Audited products |
    And I save form
    Then I should see "Menu item saved successfully" flash message
    When I go to System/ Data Audit
    And I filter "Data" as contains "Audited products"
    Then I should see following grid containing rows:
      | Entity type              | Entity name                         | Action |
      | Back-Office Menu: Global | application_menu / Audited products | Update |
    And I should see "Title" in grid

  Scenario: Hiding a menu item is recorded as a change of that item
    Given I go to System/ Menus
    And I click view "application_menu" in grid
    When I click on "Audited products" in tree "Sidebar Menu Tree"
    And I click "Hide"
    Then I should see "Menu item is hidden now" flash message
    When I go to System/ Data Audit
    And I filter "Entity name" as contains "Audited products"
    Then I should see following grid containing rows:
      | Entity type              | Entity name                         | Action |
      | Back-Office Menu: Global | application_menu / Audited products | Update |
    And I should see "Active" in grid

  Scenario: The change history of a menu item is shown on the page of that item
    Given I go to System/ Menus
    And I click view "application_menu" in grid
    When I click on "Audited products" in tree "Sidebar Menu Tree"
    And I click "Change History"
    Then I should see following "Audit History Grid" grid containing rows:
      | Old Values      | New Values              |
      | Title: Products | Title: Audited products |
    And I close ui dialog

  Scenario: Deleting a menu item records where the items nested into it went
    Given I go to System/ Menus
    And I click view "application_menu" in grid
    When I click "Create Menu Item"
    And I fill "Menu Form" with:
      | Title | Audited parent  |
      | URI   | #audited-parent |
    And I save form
    Then I should see "Menu item saved successfully" flash message
    When I click "Create Menu Item"
    And I fill "Menu Form" with:
      | Title | Audited child  |
      | URI   | #audited-child |
    And I save form
    Then I should see "Menu item saved successfully" flash message
    When I click on "Audited parent" in tree "Sidebar Menu Tree"
    And I click "Delete"
    And I click "Yes, Delete" in modal window
    Then I should see "Menu item successfully deleted" flash message
    And I should see "Audited child" belongs to "application_menu" in tree "Sidebar Menu Tree"
    When I go to System/ Data Audit
    And I filter "Entity name" as contains "Audited "
    Then I should see following grid containing rows:
      | Entity type              | Entity name                       | Action |
      | Back-Office Menu: Global | application_menu / Audited parent  | Remove |
      | Back-Office Menu: Global | application_menu / Audited child   | Update |
    And I should see "Parent" in grid

  Scenario: Data Audit is filtered by the back-office menu levels
    Given I go to System/ Data Audit
    When I check "Back-Office Menu: Global" in "Entity Type" filter
    Then I should see "Back-Office Menu: Global" in grid
    And I should see "Audited products" in grid
    When I check "Back-Office Menu: User" in "Entity Type" filter
    Then I should see "Audited products" in grid
