@fixture-OroUserBundle:user.yml
@fixture-LanguageFixture.yml
Feature: Applying language settings in system configuration
  Scenario: Reset user language settings when language no more allowed
    Given I login as "admin" user
    When I go to System/Configuration
    And I click "Language settings"
    And I fill "Language Settings System Config Form" with:
      | Supported languages | German |
    And I save form

    When I login as "charlie" user
    And I click My Configuration in user menu
    And I click "Language settings"
    And I fill "Language Settings System Config Form" with:
      | Use Default Language | true   |
      | Default Language     | German |
    And I save form
    Then I should see "German"

    When I login as "admin" user
    And I go to System/Configuration
    And I click "Language settings"
    And I fill "Language Settings System Config Form" with:
      | Supported languages | English |
    And I save form

    When I login as "charlie" user
    And I click My Configuration in user menu
    And I click "Language settings"

    And I should see "English"
