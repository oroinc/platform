Feature: Quick access menu
  In order to quick access to some page in application
  As crm user
  I need to have link to history, favorites and most viewed pages

  Scenario: History
    Given I login as administrator
    And I go to System/ User Management/ Users
    And go to Reports & Segments/ Manage Custom Reports
    And go to System/ User Management/ Users
    And go to System/ Configuration
    And go to System/ User Management/ Users
    And go to System/ Configuration
    And go to Dashboards/ Dashboard
    When I click icon bars
    Then History must looks like:
      | Configuration - System                     |
      | Users - User Management - System           |
      | Manage Custom Reports - Reports & Segments |

  Scenario: Most viewed pages
    Given I choose Most Viewed tab
    Then Most Viewed must looks like:
      | Users - User Management - System           |
      | Configuration - System                     |
      | Manage Custom Reports - Reports & Segments |

  Scenario: Add page to favorite
    Given I click icon bars
    And I go to System/ User Management/ Users
    And I add page to favorites
    And I press "Create User"
    And I fill "User" form with:
      | Username          | userName       |
      | Password          | 123123q        |
      | Re-Enter Password | 123123q        |
      | First Name        | First Name     |
      | Last Name         | Last Name      |
      | Primary Email     | email@test.com |
    And add page to favorites
    And press "Cancel"
    And go to System/ Configuration
    When I click icon bars
    And choose Favorites tab
    Then Favorites must looks like:
      | Create User - Users - User Management - System |
      | All - Users - User Management - System         |
    And I click on "All - Users - User Management - System" in Favorites
    And John Doe must be first record

  Scenario: Remove page from favorite
    Given I click icon bars
    And choose Favorites tab
    And I remove "All - Users - User Management - System" from favorites
    And Favorites must looks like:
      | Create User - Users - User Management - System |
    When I click on "Create User - Users - User Management - System" in Favorites
    Then  "User" form must contains values:
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
