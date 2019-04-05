@ticket-BAP-15136

Feature: User reset password
  In order to manage own profile
  As an Administrator
  I need to be able to reset password in my profile

  Scenario: Create new user
    Given I login as administrator
    And I click My User in user menu
    When I follow "More actions"
    And I click "Reset password"
    And I confirm reset password
    Then I should see "Your password was reset by administrator. Please, check your email for details."
    And I should see "Password reset request has been sent to "
    And I should be on Login page
