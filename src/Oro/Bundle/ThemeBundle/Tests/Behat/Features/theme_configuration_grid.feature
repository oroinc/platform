@regression
@fixture-OroThemeBundle:theme_configuration.yml

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

  Scenario: Sort by Name
    Given I should see following grid:
      | Name            |
      | Golden Carbon   |
      | Refreshing Teal |
    When I sort grid by "Name"
    Then I should see following grid:
      | Name            |
      | Custom          |
      | Default         |
      | Golden Carbon   |
      | Refreshing Teal |
    When I sort grid by "Name" again
    Then I should see following grid:
      | Name            |
      | Refreshing Teal |
      | Golden Carbon   |
      | Default         |
      | Custom          |
    And I reset "Theme Configurations Grid" grid

  Scenario: Sort by Description
    Given I should see following grid:
      | Description         |
      |                     |
      |                     |
      | Default Description |
      | Custom              |
    When I sort grid by "Description"
    Then I should see following grid:
      | Description         |
      | Custom              |
      | Default Description |
    When I sort grid by "Description" again
    Then I should see following grid:
      | Description         |
      |                     |
      |                     |
      | Default Description |
      | Custom              |
    And I reset "Theme Configurations Grid" grid

  Scenario: Sort by Theme
    Given I should see following grid:
      | Theme         |
      | golden_carbon |
      | default       |
      | default       |
      | custom        |
    When I sort grid by "Theme"
    Then I should see following grid:
      | Theme         |
      | custom        |
      | default       |
      | default       |
      | golden_carbon |
    When I sort grid by "Theme" again
    Then I should see following grid:
      | Theme         |
      | golden_carbon |
      | default       |
      | default       |
      | custom        |
    And I reset "Theme Configurations Grid" grid
