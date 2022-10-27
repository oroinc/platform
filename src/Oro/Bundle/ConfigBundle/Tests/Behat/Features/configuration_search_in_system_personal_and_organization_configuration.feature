@regression
Feature: Configuration Search in System, Personal and Organization configuration
  In order to find the configuration options by their group names, names and descriptions (hints)
  As an Administrator
  I would like to have a search box in system/organization/website/user configuration trees

  Scenario: The Configuration Search Box in the System Configuration Settings
    Given I login as administrator
    And I go to System/ Configuration
    When I type "application" in "Configuration Quick Search"
    Then I should see "Application Settings" in the "Configuration Sidebar Content" element
    And I should not see "Localization" in the "Configuration Sidebar Content" element
    When I click "Clear Sidebar Search"
    And I expand all on configuration sidebar
    Then I should see "Localization" in the "Configuration Sidebar Content" element

  Scenario: Search should work in fuzzy mode if no result was found
    When I type "Routin" in "Configuration Quick Search"
    Then I should see "Routing" in the "Configuration Sidebar Content" element
    And I should not see "Marketing" in the "Configuration Sidebar Content" element
    When I type "RoutinW" in "Configuration Quick Search"
    Then I should see "Routing" in the "Configuration Sidebar Content" element
    And I should see "Marketing" in the "Configuration Sidebar Content" element

  Scenario: Search should show sections which contain found settings
    When I type "url" in "Configuration Quick Search"
    Then I should see "Routing" in the "Configuration Sidebar Content" element
    And I should not see "Localization" in the "Configuration Sidebar Content" element
    When I click "Clear Sidebar Search"
    And I expand all on configuration sidebar
    Then I should see "Localization" in the "Configuration Sidebar Content" element

  Scenario: Settings tooltips should be searchable
    When I type "permitted for upload" in "Configuration Quick Search"
    Then I should see "Upload Settings" in the "Configuration Sidebar Content" element
    And I should not see "Localization" in the "Configuration Sidebar Content" element
    When I click "Clear Sidebar Search"
    And I expand all on configuration sidebar
    Then I should see "Localization" in the "Configuration Sidebar Content" element

  Scenario: Settings with search type choice should be searchable
    When I type "Never" in "Configuration Quick Search"
    Then I should see "Routing" in the "Configuration Sidebar Content" element
    And I should not see "Localization" in the "Configuration Sidebar Content" element
    When I click "Clear Sidebar Search"
    And I expand all on configuration sidebar
    Then I should see "Localization" in the "Configuration Sidebar Content" element

  Scenario: If the user types section name, all child settings should be displayed
    When I type "websites" in "Configuration Quick Search"
    Then I should see "Routing" in the "Configuration Sidebar Content" element
    And I should see "Sitemap" in the "Configuration Sidebar Content" element
    And I should not see "Localization" in the "Configuration Sidebar Content" element
    When I click "Clear Sidebar Search"
    And I expand all on configuration sidebar
    Then I should see "Localization" in the "Configuration Sidebar Content" element

  Scenario: The search box should be frozen while scrolling the settings tree
    When I expand all on configuration sidebar
    # Scroll to last sidebar section by hovering on it
    And I hover on "Sidebar Last Section"
    Then I should see "Search Input" element inside "SidebarConfigMenu" element
    And I collapse all on configuration sidebar

  Scenario: Check that search text is highlighted in the configuration section label
    When I type "url" in "Configuration Quick Search"
    Then I should see "Highlight Container" element inside "Configuration Section Label" element
    And I should see "Highlighted Text" element with text "URL" inside "Configuration Section Label" element

  Scenario: Check that search text is highlighted in the option label
    When I type "applic" in "Configuration Quick Search"
    Then I should see "Highlight Container" element inside "Configuration Option Label" element
    And I should see "Highlighted Text" element with text "Applic" inside "Configuration Option Label" element

  Scenario: Check that search text is highlighted in the configuration page title
    Then I should see "Highlight Container" element inside "Configuration Page" element
    And I should see "Highlighted Text" element with text "Applic" inside "Configuration Page Title" element

  Scenario: Check that search text is highlighted in the configuration menu
    Then I should see "Highlight Container" element inside "Configuration Menu Item" element
    And I should see "Highlighted Text" element with text "Applic" inside "Configuration Menu Item" element

  Scenario: Check highlighted tooltip content after searching in System Configuration
    When I type "notifications" in "Configuration Quick Search"
    Then I should see "Highlighted Tooltip Icon" element inside "Configuration Page" element
    And I click on "Highlighted Tooltip Icon"
    Then I should see "Highlighted Text" element with text "notifications" inside "Tooltip Container" element
    And I click on empty space
    Then I should not see "Highlighted Text"
    And I should see "Highlighted Tooltip Icon" element inside "Configuration Page" element

  Scenario: Check that clearing the search removes the highlighting
    When I click "Clear Sidebar Search"
    Then I should not see "Highlight Container" element inside "Configuration Section Label" element
    And I should not see "Highlight Container" element inside "Configuration Option Label" element
    And I should not see "Highlight Container" element inside "Configuration Page" element
    And I should not see "Highlight Container" element inside "Configuration Menu Item" element
    And I should not see "Highlighted Tooltip Icon" element inside "Configuration Page" element

  Scenario: The Configuration Search Box in Personal Configuration
    Given I click My Configuration in user menu
    And I type "localization" in "Configuration Quick Search"
    Then I should see "Localization" in the "Configuration Sidebar Content" element
    And I should not see "Email Configuration" in the "Configuration Sidebar Content" element

  Scenario: The Configuration Search Box in Organization Configuration
    Given I go to System/ User Management/ Organizations
    And I click Configuration "Oro" in grid
    And I type "localization" in "Configuration Quick Search"
    Then I should see "Localization" in the "Configuration Sidebar Content" element
    And I should not see "Quick Order Form" in the "Configuration Sidebar Content" element

  Scenario: The Configuration Search show/hide all results
    Given I go to System/ Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    When I type "Calendar" in "Configuration Quick Search"
    And I should see "Show hidden items"
    And I should not see "User bar"
    And I should not see "Navigation bar"
    And I should not see "Data Grid settings"
    And I click "Show hidden items"
    Then I should see "User bar"
    And I should see "Navigation bar"
    And I should see "Data Grid settings"
    And I reload the page
    And I should see "User bar"
    And I should see "Navigation bar"
    And I should see "Data Grid settings"
    When I click "Show search results only"
    And I should not see "User bar"
    And I should not see "Navigation bar"
    And I should not see "Data Grid settings"
    And I click "Show hidden items"
    Then I type "Items" in "Configuration Quick Search"
    When I click "Show search results only"
    And I should not see "User bar"
    And I should not see "Navigation bar"
    And I should see "Data Grid settings"
    And I should see "Activity lists"
    Then I type "Some not valid request" in "Configuration Quick Search"
    And I should see "User bar"
    And I should see "Navigation bar"
    And I should see "Map Settings"
    And I should see "Sidebar settings"
    And I should see "Reports settings"
