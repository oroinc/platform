@ticket-BAP-11232
@automatically-ticket-tagged
@skip
@BAP-16190
Feature: Quick access menu
  In order to quick access to some page in application
  As crm user
  I need to have link to history, favorites and most viewed pages

  Scenario: History
    Given I login as administrator
    And History is empty
    And I go to System/ User Management/ Users
    And History is empty
    And go to next pages:
      | Reports & Segments/ Manage Custom Reports |
      | System/ User Management/ Users            |
      | Dashboards/ Manage Dashboards             |
      | System/ User Management/ Users            |
      | Dashboards/ Manage Dashboards             |
      | Dashboards/ Dashboard                     |
    Then History must looks like:
      | Manage Dashboards - Dashboards             |
      | Users - User Management - System           |
      | Manage Custom Reports - Reports & Segments |

  Scenario: Most viewed pages
    Given I choose Most Viewed tab
    Then Most Viewed must looks like:
      | Users - User Management - System           |
      | Manage Dashboards - Dashboards             |
      | Manage Custom Reports - Reports & Segments |

  Scenario: Add page to favorite
    Given I click icon bars
    And I go to System/ User Management/ Users
    And I add page to favorites
    And I click "Create User"
    And I fill "User Form" with:
      | Username          | userName       |
      | Password          | Pa$$w0rd       |
      | Re-Enter Password | Pa$$w0rd       |
      | First Name        | First Name     |
      | Last Name         | Last Name      |
      | Primary Email     | email@test.com |
    And add page to favorites
    And click "Cancel"
    And go to System/ Configuration
    And I reload the page
    When I click icon bars
    And choose Favorites tab
    Then Favorites must looks like:
      | Users - User Management - System                |
      | Create User - Users - User Management - System  |
    And I click on "Users - User Management - System" in Favorites
    And John Doe must be first record

  Scenario: Remove page from favorite
    Given I click icon bars
    And choose Favorites tab
    And I remove "Users - User Management - System" from favorites
    And Favorites must looks like:
      | Create User - Users - User Management - System |
    When I click on "Create User - Users - User Management - System" in Favorites
    Then  "User Form" must contains values:
      | Username          |    |
      | Password          |    |
      | Re-Enter Password |    |
      | First Name        |    |
      | Last Name         |    |
      | Primary Email     |    |
    And I remove page from favorites
    And click icon bars
    And choose Favorites tab
    And there are no pages in favorites
