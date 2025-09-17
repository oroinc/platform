@regression
@ticket-BB-17504

Feature: Special character in multiple select placeholder
  In order to have correct translation placeholder
  As an Administrator
  I want to see that placeholder in not encoded

  Scenario: Feature Background
    Given I login as administrator

  Scenario: Check placeholder for multiple select2 element
    Given I go to System/Localization/Translations
    And I check "English" in Language filter
    And I filter Key as is equal to "oro.attachment.mimetypes.placeholder"
    And I edit first record from grid:
      | Translated Value | Select <> Académie française ty'pe... |
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    And I click "Create field"
    And I fill form with:
      | Field name   | TestField      |
      | Storage type | Table column   |
      | Type         | Multiple Files |
    And click "Continue"
    Then I should see that multiple select2 "Allowed MIME types" contains "Select &lt;&gt; Académie française ty'pe..." placeholder
