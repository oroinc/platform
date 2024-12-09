@regression
@behat-test-env
@ticket-BB-24484

Feature: Check captcha protected forms

  Scenario: Enable CAPTCHA protection
    Given I login as administrator
    When I go to System / Configuration
    And I follow "System Configuration/Integrations/CAPTCHA Settings" on configuration sidebar

    And uncheck "Use default" for "Enable CAPTCHA protection" field
    And I check "Enable CAPTCHA protection"

    And uncheck "Use default" for "CAPTCHA service" field
    And I fill in "CAPTCHA service" with "Dummy"

    And uncheck "Use default" for "Protect Forms" field
    And I check "User Reset Password Form"
    And I check "User Login Form"

    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check CAPTCHA protection for User Reset Password Form
    When I click Logout in user menu
    Then I should see "Login"

    When I click "Forgot your password?"
    Then I should see "Return to Login"
    And I should see "Captcha"

    When I fill in "Username or Email" with "admin"
    And I fill in "Captcha" with "invalid"
    And I click "Request"
    Then I should see "The form cannot be sent because you did not passed the anti-bot validation. If you are a human, please contact us."

    When I fill in "Username or Email" with "admin"
    And I fill in "Captcha" with "valid"
    And I click "Request"
    Then I should see "If there is a user account associated with admin you will receive an email with a link to reset your password."
    And I click "Return to Login"

  Scenario: Check CAPTCHA protection for User Login Form
    Given I should see "Login"
    And I should see "Captcha"

    When I fill in "Username" with "admin"
    And I fill in "Password" with "admin"
    And I fill in "Captcha" with "invalid"
    And I click "Log in"
    Then I should see "The form cannot be sent because you did not passed the anti-bot validation. If you are a human, please contact us."

    When I fill in "Username" with "admin"
    And I fill in "Password" with "admin"
    And I fill in "Captcha" with "valid"
    And I click "Log in"
    Then I should see "Dashboard"
