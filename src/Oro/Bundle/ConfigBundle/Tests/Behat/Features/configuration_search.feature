@regression
Feature: Configuration Search in System, Personal and Organization configuration
  In order to find the configuration options by their group names
  As an Administrator
  I would like to have a search box in system/organization/website/user configuration trees

  Scenario: The Configuration Search Box in the System Configuration Settings
    Given I login as administrator
    And I go to System/ Configuration
    And I type "Setting" in "Quick Search"
    And I should see "Application Settings" in the "Configuration Sidebar Content" element
    And I should not see "Localization" in the "Configuration Sidebar Content" element

  Scenario: The Configuration Search Box in Personal Configuration
    Given I click My Configuration in user menu
    And I type "Setting" in "Quick Search"
    And I should see "Language settings" in the "Configuration Sidebar Content" element
    And I should not see "Email Configuration" in the "Configuration Sidebar Content" element

  Scenario: The Configuration Search Box in Organization Configuration
    Given I go to System/ User Management/ Organizations
    And I click Configuration "Oro" in grid
    And I type "Setting" in "Quick Search"
    And I should see "Display settings" in the "Configuration Sidebar Content" element
    And I should not see "Quick Order Form" in the "Configuration Sidebar Content" element
