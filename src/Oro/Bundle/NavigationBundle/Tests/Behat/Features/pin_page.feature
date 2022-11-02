@regression
@ticket-BAP-11237
@automatically-ticket-tagged
Feature: Pin page
  In order to have fast access to some pages in system
  As crm user
  I need pin some page and return to it with the same state later

  Scenario: Pin filtered grid
    Given I login as administrator
    And the following users:
      | First Name | Last Name | Email               | Username |
      | Jane       | Pena      | jane@example.com    | jane     |
      | Charlie    | Sheen     | charlie@sheen.com   | charlie  |
      | Leah       | Williams  | leah@example.com    | leah     |
      | Beatrice   | Walker    | walker@example.com  | walker   |
      | Dolores    | Clark     | clark@example.com   | clark    |
      | Gregory    | Fuller    | gregory@example.com | gregory  |
    And I go to System/User Management/Users
    And there are 7 records in grid
    And I filter First name as contains "Charlie"
    And I filter Last name as contains "Sheen"
    And there is one record in grid
    And Users link must not be in pin holder
    And I should see that "Pin/unpin the page" Button is not highlighted
    When I pin page
    Then Users link must be in pin holder
    And I should see that "Pin/unpin the page" Button is highlighted
    And I should see that "Users" pin is active

  Scenario: Follow pinned link
    Given I reset First name filter
    And reset Last name filter
    Then Users link must be in pin holder
    And I should see that "Users" pin is inactive
    And there are 7 records in grid
    When go to Dashboards/Dashboard
    And follow Users link in pin holder
    Then I should be on User Index page
    And there is one record in grid
    And I should see Charlie Sheen in grid with following data:
      | Primary Email | charlie@sheen.com |
      | Username      | charlie           |
    And I unpin page
    And Users link must not be in pin holder

  Scenario: Pin sorted grid
    Given go to Dashboards/Dashboard
    And I go to System/User Management/Users
    And there are 7 records in grid
    And John Doe must be first record
    And I sort grid by First name
    And Beatrice Walker must be first record
    When I pin page
    And go to Dashboards/Dashboard
    And I go to System/User Management/Users
    Then John Doe must be first record
    And I go to Dashboards/Dashboard
    And I follow Users link in pin holder
    And Beatrice Walker must be first record
    When I unpin page
    Then Users link must not be in pin holder

  Scenario: Matching pin should be highlighted:
    Given I filter First name as contains "Charlie"
    And I filter Last name as contains "Sheen"
    Then I should see that "Pin/unpin the page" Button is not highlighted
    When I pin page
    Then I should see that "Users" pin is active
    And I should see that "Pin/unpin the page" Button is highlighted
    When I filter First name as contains "John"
    And I filter Last name as contains "Doe"
    Then I should see that "Users" pin is inactive
    And I should see that "Pin/unpin the page" Button is not highlighted
    When I filter First name as contains "Charlie"
    Then I should see that "Users" pin is inactive
    And I should see that "Pin/unpin the page" Button is not highlighted
    When I filter Last name as contains "Sheen"
    Then I should see that "Users" pin is active
    And I should see that "Pin/unpin the page" Button is highlighted

  Scenario: Pins must keep function during grid reset
    Given I reset "Users Grid" grid
    Then I should see that "Users" pin is inactive
    And I should see that "Pin/unpin the page" Button is not highlighted
    And there are 7 records in grid
    When I follow Users link in pin holder
    Then I should see that "Users" pin is active
    And I should see that "Pin/unpin the page" Button is highlighted
    And there are 1 records in grid
    When I reset "Users Grid" grid
    Then I should see that "Users" pin is inactive
    And I should see that "Pin/unpin the page" Button is not highlighted
    And there are 7 records in grid
    When I follow Users link in pin holder
    Then I should see that "Users" pin is active
    And I should see that "Pin/unpin the page" Button is highlighted
    And there are 1 records in grid
    And I unpin page

  Scenario: Several pins can be created for the same page with different names
    Given I reset First name filter
    And reset Last name filter
    When I filter First name as contains "Charlie"
    And I filter Last name as contains "Test1"
    And I pin page
    Then Users link must be in pin holder
    When I filter Last name as contains "Test2"
    And I pin page
    Then Users (2) link must be in pin holder
    When I filter Last name as contains "Sheen"
    And I pin page
    Then Users (3) link must be in pin holder
    When I go to Dashboards/Dashboard
    And I follow Users (3) link in pin holder
    Then I should be on User Index page
    And there is one record in grid
    And I should see Charlie Sheen in grid with following data:
      | Primary Email | charlie@sheen.com |
      | Username      | charlie           |
    And I should see that "Users (3)" pin is active

  Scenario: Pin filled form
    Given go to Dashboards/Dashboard
    And go to System/User Management/Users
    And click "Create User"
    When I fill "User Form" with:
      | Username          | userName       |
      | Enabled           | Enabled        |
      | Password          | Pa$$w0rd       |
      | Re-Enter Password | Pa$$w0rd       |
      | First Name        | First Name     |
      | Last Name         | Last Name      |
      | Primary Email     | email@test.com |
      | Roles             | Administrator  |
    And Create User link must not be in pin holder
    When I pin page
    Then Create User link must be in pin holder

  Scenario: View pinned form
    Given I go to Dashboards/Dashboard
    When I follow Create User link in pin holder
    Then I should be on User Create page
    And "User Form" must contains values:
      | Username          | userName        |
      | Password          |                 |
      | Re-Enter Password |                 |
      | First Name        | First Name      |
      | Last Name         | Last Name       |
      | Primary Email     | email@test.com  |

  Scenario: Save form and view pinned form
    Given I fill "User Form" with:
      | Password          | Pa$$w0rd123     |
      | Re-Enter Password | Pa$$w0rd123     |
    And I save and close form
    And I should see "User saved" flash message
    When I follow Create User link in pin holder
    Then I should be on User Create page
    And "User Form" must contains values:
      | Username          | userName        |
      | Password          |                 |
      | Re-Enter Password |                 |
      | First Name        | First Name      |
      | Last Name         | Last Name       |
      | Primary Email     | email@test.com  |
    And I fill "User Form" with:
      | Password          | Pa$$w0rd123     |
      | Re-Enter Password | Pa$$w0rd123     |
    And I save and close form
    And I should be on User Create page
    And I should see "This value is already used."

  Scenario: View blank form
    Given I go to System/User Management/Users
    When press Create User button
    Then  "User Form" must contains values:
      | Username          |    |
      | Password          |    |
      | Re-Enter Password |    |
      | First Name        |    |
      | Last Name         |    |
      | Primary Email     |    |
      | Roles             | [] |

  Scenario: Pin multi-step form
    Given I go to Products/Products
    And click "Create Product"
    And click "Continue"
    And fill "Create Product Form" with:
      |SKU             |sku_for_pin_testing     |
      |Name            |name_for_pin_testing    |
    When I pin page
    Then I should see that "Create Product" pin is active
    When I go to Dashboards/Dashboard
    Then I should see that "Create Product" pin is inactive
    When I follow Create Product link in pin holder
    And click "Continue"
    Then I should see that "Create Product" pin is active
    And "Create Product Form" must contains values:
      |SKU             |sku_for_pin_testing     |
      |Name            |name_for_pin_testing    |
