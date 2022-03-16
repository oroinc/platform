@ticket-CRM-9335
@fixture-OroUserBundle:second-admin.yml
Feature: Private emails visibility
  In order to manage Email feature
  As an Administrator
  I should be able to manipulate visibility of private emails

  Scenario: Scenario background
    Given I login as "charlie" user
    And I click My Emails in user menu
    And there are no records in grid
    When I click "Compose"
    And fill "Email Form" with:
      | Body    | Some body     |
      | Subject | Private email |
      | To      | John Doe      |
    And I click "Send"
    Then I should see "The email was sent" flash message

  Scenario: View own private email with default admin permissions
    Given I login as "charlie" user
    When I click My Emails in user menu
    Then I should see following grid:
      | Subject                 |
      | Private email Some body |
    When I click "Search"
    And type "Private email" in "search"
    Then I should see 1 search suggestions
    When I click "Search Submit"
    When number of records should be 1
    And I should see following search results:
      | Title          | Type   |
      | Private email  | Email  |
    When I click My User in user menu
    Then I shouldn't see "Private email" email in activity list

  Scenario: View private email by second admin with default admin permissions
    Given I login as administrator
    When I click "Search"
    And type "Private email" in "search"
    Then I should see 1 search suggestions
    When I click "Search Submit"
    When number of records should be 1
    And I should see following search results:
      | Title          | Type   |
      | Private email  | Email  |
    When I click My User in user menu
    Then I shouldn't see "Private email" email in activity list

  Scenario: Change access level of the private emails to User
    Given I go to System/ User Management/ Roles
    And I click edit "Administrator" in grid
    And select following permissions:
      | User Emails | View private:User |
    And save and close form

  Scenario: View own private email with User access level
    Given I login as "charlie" user
    When I click My Emails in user menu
    Then I should see following grid:
      | Subject                 |
      | Private email Some body |
    When I click "Search"
    And type "Private email" in "search"
    Then I should see 1 search suggestions
    When I click "Search Submit"
    When number of records should be 1
    And I should see following search results:
      | Title          | Type   |
      | Private email  | Email  |

  Scenario: View private email by second admin with User access level
    Given I login as administrator
    When I click "Search"
    And type "Private email" in "search"
    Then I should see 0 search suggestions

  Scenario: Change access level of the private emails to None
    Given I go to System/ User Management/ Roles
    And I click edit "Administrator" in grid
    And select following permissions:
      | User Emails | View private:None |
    And save and close form

  Scenario: View own private email with None access level
    Given I login as "charlie" user
    When I click My Emails in user menu
    Then I should not see "Private email"
    When I click "Search"
    And type "Private email" in "search"
    Then I should see 0 search suggestions
