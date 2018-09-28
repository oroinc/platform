Feature: User system settings manage
  In order to control user registration security
  As OroCRM sales rep
  I need to be able to change password restrictions

  Scenario: Change user create password restrictions settings
    Given I login as administrator
    And I go to System/Configuration
    And follow "System Configuration/General Setup/User Settings" on configuration sidebar
    When I fill "System Config Form" with:
      | Minimal password length      | 10   |
      | Require a number             | true |
      | Require a lower case letter  | true |
      | Require an upper case letter | true |
      | Require a special character  | true |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message
    When I go to System/User Management/Users
    And click "Create User"
    And fill form with:
      | Password | 1 |
    And I save and close form
    Then I should see validation errors:
      | Password | The password must be at least 10 characters long and include a lower case letter, an upper case letter, and a special character |
