@ticket-BAP-12594
@automatically-ticket-tagged
@not-automated
Feature: Identify changes made by impersonated user in Data Audit
  In order to see changes made by impersonated users
  as any user
  I should have possibility to see, what changes have been made by impersonated users

  Scenario: Create entity by impersonated user
    Given I login as Impersonated user
    And I go to Contacts
    And I create Contact with:
    | Username | First Name | Last Name | Phone      | Email              |
    | ncave    | Nick       | Cave      | 555-NICKK  | nickcave@music.com |
    And I save form
    When I click "Change History"
    Then I should see "Action" with "Create"
    And I should see "impersonated from" in the form
    And I should see my IP address

  Scenario: Change entity by impersonated user
    Given I go to Contacts
    And I edit "ncave" entity
    And I fill in "Middle Name" with "Johnson"
    And save form
    When I click "Change History"
    Then I should see "Action" with "Change"
    And I should see "impersonated from" in the form
    And I should see my IP address

  Scenario: Delete entity by impersonated user
    Given I go to Contacts
    And I delete "ncave" entity
    And log out
    And I login as "Administrator" user
    When I go to System/Data Audit
    Then I should see Action in grid with following data:
    | Create |
    | Edit   |
    | Delete |
    And I should see 3 records "impersonated from" in the grid
    And I should see my IP address
