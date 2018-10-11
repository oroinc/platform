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
    When I pin page
    Then Users link must be in pin holder

  Scenario: Follow pinned link
# @todo Remove or uncomment. BAP-11782
#    Given I reset First name filter
#    And reset Last name filter
#    And there are 7 records in grid
    And go to Dashboards/Dashboard
    When follow Users link in pin holder
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
# @todo Remove or uncomment. BAP-11782
#    And go to Dashboards/Dashboard
#    And I go to System/User Management/Users
#    Then John Doe must be first record
    And I go to Dashboards/Dashboard
    And I follow Users link in pin holder
    And Beatrice Walker must be first record

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

# @todo Remove or uncomment. BAP-11782
#  Scenario: View blank form
#    Given I go to System/User Management/Users
#    When press Create User button
#    Then  "User Form" must contains values:
#      | Username          |    |
#      | Password          |    |
#      | Re-Enter Password |    |
#      | First Name        |    |
#      | Last Name         |    |
#      | Primary Email     |    |
#      | Roles             | [] |
