@ticket-BAP-17661
@fixture-OroLocaleBundle:LocalizationFixture.yml
@regression

Feature: User role entity permissions translation
  In order to see role permissions on view page
  As an Administrator
  I want to be able to see entity labels in current language

  Scenario: Check that entity labels in the permissions grid are correctly translated on еру role view page
    Given I login as administrator
    When go to System / User Management / Roles
    And I click view Administrator in grid
    Then the role has following active permissions:
      | Account    | View:Global |
      | Attachment | View:Global |

    # Prepare new label for Attachment entity in german language
    When I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English, German Localization] |
      | Default Localization  | German Localization            |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I go to System / Entities / Entity Management
    And filter Name as is equal to "Attachment"
    And I click edit Attachment in grid
    When I fill form with:
      | Label | Attachment_DE |
    And I save and close form
    Then I should see "Entity saved" flash message
    When I go to System / Localization / Translations
    And I click "Update Cache"
    Then I should see "Translation Cache has been updated" flash message

    When go to System / User Management / Roles
    And I click view Administrator in grid
    Then the role has following active permissions:
      | Account       | View:Global |
      | Attachment_DE | View:Global |
