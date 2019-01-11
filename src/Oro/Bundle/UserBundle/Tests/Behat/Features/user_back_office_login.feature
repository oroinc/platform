@fixture-OroUserBundle:user.yml
@fixture-OroLocaleBundle:ZuluLocalization.yml

Feature: User back office login
  In order to manage access
  As an application administrator
  I want to be sure that "Login" functionality is working fine

  Scenario: Feature Background
    And I enable the existing localizations

  Scenario: successful back office login using username
    Given I am on Login page
    And I fill "Login Form" with:
      | Username | admin |
      | Password | admin |
    When I click "Log in"
    Then I should be on Admin Dashboard page

  Scenario: failing back office login using username with a wrong password
    Given I am logged out
    And I am on Login page
    And I fill "Login Form" with:
      | Username | admin     |
      | Password | incorrect |
    When I click "Log in"
    Then I should see "Your login was unsuccessful. Please check your e-mail address and password before trying again. If you have forgotten your password, follow \"Forgot your password?\" link."

  Scenario: successful back office login using email
    Given I am logged out
    And I am on dashboard
    And I am on Login page
    And I fill "Login Form" with:
      | Username | charlie@example.com |
      | Password | charlie |
    When I click "Log in"
    Then I should be on Admin Dashboard page

  Scenario: failing back office login using email with a wrong password
    Given I am logged out
    And I am on Login page
    And I fill "Login Form" with:
      | Username | charlie@example.com |
      | Password | charlie@example.com |
    When I click "Log in"
    Then I should see "Your login was unsuccessful. Please check your e-mail address and password before trying again. If you have forgotten your password, follow \"Forgot your password?\" link."

  Scenario: failing back office login of nonexistent user
    Given I am logged out
    And I am on Login page
    When I login as "user" user
    Then I should see "Your login was unsuccessful. Please check your e-mail address and password before trying again. If you have forgotten your password, follow \"Forgot your password?\" link."

  Scenario: Check that username field has been filled up
    Given I am logged out
    And I am on Login page
    And I fill "Login Form" with:
      | Username |  |
      | Password | admin |
    When I click "Log in"
    Then  I am on Login page

  Scenario: Check that password field has been filled up
    Given I am logged out
    And I am on Login page
    And I fill "Login Form" with:
      | Username | admin |
      | Password |  |
    When I click "Log in"
    Then  I am on Login page

  Scenario: Check that username and password fields have been filled up
    Given I am logged out
    And I am on Login page
    And I fill "Login Form" with:
      | Username |  |
      | Password |  |
    When I click "Log in"
    Then  I am on Login page

  Scenario: Redirect already logged user
    Given I am logged out
    And I login as "admin" user
    And I should be on Admin Dashboard page
    When I am on Login page
    Then I should be on Admin Dashboard page

  Scenario: back office user logout
    Given I am logged out
    And I login as "admin" user
    When I click Logout in user menu
    Then I am on Login page

  Scenario: Add translation for unsuccessful login error on back office and switch to second language
    Given I login as administrator
    And I go to System / Configuration
    When I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And fill form with:
      | Enabled Localizations | [English, Zulu_Loc] |
      | Default Localization  | Zulu_Loc            |
    And I submit form
    And go to System/Localization/Translations
    When filter Translated Value as is empty
    And filter English translation as contains "Your login was unsuccessful"
    Then I edit "oro_user.login.errors.bad_credentials" Translated Value as "Your login was unsuccessful - Zulu"
    And I should see following records in grid:
      |Your login was unsuccessful - Zulu|
    And click "Update Cache"

  Scenario: Check translated unsuccessful login error
    Given I am logged out
    And I am on Login page
    And I fill "Login Form" with:
      | Username | admin     |
      | Password | incorrect |
    When I click "Log in"
    Then I should see "Your login was unsuccessful - Zulu"
