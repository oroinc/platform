Feature: User menu
  In order to have some quick links related to current user
  As a OroCRM user
  I need to user navigation menu

  Scenario: My profile
    Given I login as administrator
    When I click My User in user menu
    Then I should be on User Profile View page

  Scenario: My configuration
    Given I click My Configuration in user menu
    Then I should be on User Profile Configuration page

  Scenario: My Emails
    Given I click My Emails in user menu
    And I should be on User Emails page
    And there is no records in grid
    When I follow "Compose"
    And fill form with:
      | Subject | Test mail for me              |
      | To      | John                          |
      | Body    | This is very simple test mail |
    And press "Send"
    Then I should see "The email was sent" flash message
    And number of records should be 1

  Scenario: My Calendar
    Given I click My Calendar in user menu
    Then I should be on Default Calendar View page

  Scenario: Logout
    Given I click Logout in user menu
    And I should be on Login page
