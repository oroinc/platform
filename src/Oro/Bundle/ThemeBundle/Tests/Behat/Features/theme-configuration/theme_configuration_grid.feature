@regression
@fixture-OroThemeBundle:theme_configuration_grid.yml

Feature: Theme Configuration Grid

  Scenario: Check Name filter
    Given I login as administrator
    And I go to System / Theme Configurations
    When I filter Name as Contains "Default"
    Then I should see following grid:
      | Name    |
      | Default |
    And records in grid should be 1
    And I reset Name filter

  Scenario: Check Description filter
    Given I filter Description as Contains "Default"
    Then I should see following grid:
      | Description         |
      | Default Description |
    And records in grid should be 1
    And I reset Description filter

  Scenario: Check Theme filter
    Given I filter Theme as Contains "default"
    Then I should see following grid:
      | Theme   |
      | default |
    And records in grid should be 2
    And I reset Theme filter

  Scenario: Enable & Check Type filter
    Given records in grid should be 3
    When I show filter "Type" in "Theme Configurations Grid" grid
    And I check "Storefront" in "Type: All" filter strictly
    Then I should see following grid:
      | Name            |
      | Refreshing Teal |
    And records in grid should be 3
    And I reset "Type: Storefront" filter

  Scenario: Sort by Name
    Given I should see following grid:
      | Name            |
      | Refreshing Teal |
    When I sort grid by "Name"
    Then I should see following grid:
      | Name            |
      | Custom          |
      | Default         |
      | Refreshing Teal |
    When I sort grid by "Name" again
    Then I should see following grid:
      | Name            |
      | Refreshing Teal |
      | Default         |
    And I reset "Theme Configurations Grid" grid

  Scenario: Sort by Description
    Given I should see following grid:
      | Description         |
      |                     |
      | Default Description |
    When I sort grid by "Description"
    Then I should see following grid:
      | Description         |
      | Custom              |
      | Default Description |
    When I sort grid by "Description" again
    Then I should see following grid:
      | Description         |
      |                     |
      | Default Description |
    And I reset "Theme Configurations Grid" grid

  Scenario: Sort by Theme
    Given I should see following grid:
      | Theme   |
      | default |
      | default |
      | custom  |
    When I sort grid by "Theme"
    Then I should see following grid:
      | Theme   |
      | custom  |
      | default |
    When I sort grid by "Theme" again
    Then I should see following grid:
      | Theme   |
      | default |
      | default |
      | custom  |
    And I reset "Theme Configurations Grid" grid

  Scenario: Enable column "Type" and Sort by it
    Given I should see following grid:
      | Name            |
      | Refreshing Teal |
    And I show column Type in grid
    When I sort grid by "Type"
    Then I should see following grid:
      | Name            | Type       |
      | Refreshing Teal | Storefront |
      | Default         | Storefront |
      | Custom          | Storefront |
    When I sort grid by "Type" again
    Then I should see following grid:
      | Name            | Type       |
      | Custom          | Storefront |
      | Default         | Storefront |
      | Refreshing Teal | Storefront |
    And I reset "Theme Configurations Grid" grid
