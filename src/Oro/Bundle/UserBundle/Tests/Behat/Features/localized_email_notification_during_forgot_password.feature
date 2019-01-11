@regression
@ticket-BAP-17336
@fixture-OroUserBundle:UserLocalizations.yml

Feature: Localized email notification during forgot password
  In order to receive forgot password email
  As a user
  I need to receive email in predefined language

  Scenario: Prepare configuration with different languages on each level
    Given I login as administrator
    When I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English, French Localization, German Localization] |
      | Default Localization  | French Localization                                 |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I go to System / User Management / Organizations
    And click Configuration "Oro" in grid
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use System" for "Default Localization" field
    And I fill form with:
      | Default Localization | German Localization |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I click My Configuration in user menu
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use Organization" for "Default Localization" field
    And I fill form with:
      | Default Localization | English |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: A user should get an email in a language of his config underuse of "Forgot password" functionality
    Given I go to System / Emails / Templates
    When I filter Template Name as is equal to "user_reset_password"
    And I click "edit" on first row in grid
    And fill "Email Template Form" with:
      | Subject | English Forgot Password Subject |
      | Content | English Forgot Password Body    |
    And I click "French"
    And fill "Email Template Form" with:
      | Subject | French Forgot Password Subject |
      | Content | French Forgot Password Body    |
    And I click "German"
    And fill "Email Template Form" with:
      | Subject | German Forgot Password Subject |
      | Content | German Forgot Password Body    |
    And I submit form
    Then I should see "Template saved" flash message
    Given I click Logout in user menu
    And I click "Forgot your password?"
    And I fill form with:
      | Username or Email | admin@example.com |
    And I confirm reset password
    Then I should see "If there is a user account associated with ...@example.com you will receive an email with a link to reset your password."
    And Email should contains the following:
      | To      | admin@example.com               |
      | Subject | English Forgot Password Subject |
      | Body    | English Forgot Password Body    |
