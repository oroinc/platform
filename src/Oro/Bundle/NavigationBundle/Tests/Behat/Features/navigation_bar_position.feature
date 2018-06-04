@regression
@ticket-BAP-16267
Feature: Navigation bar position
  In order to provide better navigation for users
  As a configurator
  I want to be sure that surfing is available despite the main menu position

  Scenario: Precondition
    When I login as administrator
    Then menu must be on left side
    And menu must be minimized

  Scenario Outline: Navigation through the minimized main menu with "Left" navigation bar position
    When I go to <menu>
    Then I should see <breadcrumb>

    Examples:
      | menu                               | breadcrumb                           |
      | Dashboards/ Manage Dashboards      | "Dashboards/ Manage Dashboards"      |
      | System/ Localization/ Translations | "System/ Localization/ Translations" |
      | Activities/ Calendar Events        | "Activities/ Calendar Events"        |

  Scenario: Filter menu items using search input
    When I select "Reports & Segments" in the side menu
    And I fill in "MenuSearch" with "To"
    Then I should see "Total Forecast"
    When I go to Reports & Segments/ Reports/ Opportunities/ Total Forecast
    Then I should see "Reports & Segments/ Reports/ Opportunities/ Total Forecast"

  Scenario: Expand main manu
    When I click "Main Menu Toggler"
    Then menu must be expanded
    When I click "Main Menu Toggler"
    Then menu must be minimized
    When click "Main Menu Toggler"
    Then menu must be expanded

  Scenario Outline: Navigation through the expanded main menu with "Left" navigation bar position
    When I go to <menu>
    Then I should see <breadcrumb>

    Examples:
      | menu                                | breadcrumb                            |
      | Dashboards/ Manage Dashboards       | "Dashboards/ Manage Dashboards"       |
      | System/ Localization/ Translations  | "System/ Localization/ Translations"  |
      | System/ Entities/ Entity Management | "System/ Entities/ Entity Management" |
      | Activities/ Calendar Events         | "Activities/ Calendar Events"         |

  Scenario Outline: Navigation through the main menu with "Left" navigation bar position with changing
                    of minimized/expanded mode on each step
    When I click "Main Menu Toggler"
    And go to <menu>
    Then I should see <breadcrumb>

    Examples:
      | menu                                | breadcrumb                            |
      | Dashboards/ Manage Dashboards       | "Dashboards/ Manage Dashboards"       |
      | System/ Localization/ Translations  | "System/ Localization/ Translations"  |
      | System/ Entities/ Entity Management | "System/ Entities/ Entity Management" |
      | Activities/ Calendar Events         | "Activities/ Calendar Events"         |

  Scenario: Change navigation bar position from "Left" to "Top"
    When I go to System/ Configuration
    And follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And uncheck "Use default" for "Position" field
    And select "Top" from "Position"
    And I save setting
    Then menu must be at top

  Scenario Outline: Navigation through the main menu with "Top" navigation bar position
    When I go to <menu>
    Then I should see <breadcrumb>

    Examples:
      | menu                               | breadcrumb                           |
      | Dashboards/ Manage Dashboards      | "Dashboards/ Manage Dashboards"      |
      | System/ Localization/ Translations | "System/ Localization/ Translations" |
      | Activities/ Calendar Events        | "Activities/ Calendar Events"        |
