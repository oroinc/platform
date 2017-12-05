@regression
@ticket-BAP-14097
@automatically-ticket-tagged
Feature: Mass reset translations
  ToDo: BAP-16103 Add missing descriptions to the Behat features
  Scenario: Reset several translations
    Given I login as administrator
    When I go to System/Localization/Translations
    # Next step required to see mass action button without horizontal scroll
    And I hide all columns in grid except Translated Value, Key
    And I sort grid by Key
    And I check first 2 records in grid
    And I click "Reset Translation" link from mass action dropdown
    Then I should see "Reset Confirmation"
    When I click "Reset" in modal window
    Then I should see "Selected translations were reset to their original values." flash message
    And I should see that Translated Value in 1 row is empty
    And I should see that Translated Value in 2 row is empty
    And I should see that Translated Value in 3 row is not empty

  Scenario: Reset translations except several
    Given I login as administrator
    When I go to System/Localization/Translations
    And I hide all columns in grid except Translated Value, Key
    And I sort grid by Key
    And I check All Visible records in grid
    And I uncheck first 4 records in grid
    And I click "Reset Translation" link from mass action dropdown
    Then I should see "Reset Confirmation"
    When I click "Reset" in modal window
    Then I should see "Selected translations were reset to their original values." flash message
    And I should see that Translated Value in 3 row is not empty
    And I should see that Translated Value in 4 row is not empty
    And I should see that Translated Value in 5 row is empty

  Scenario: Reset all translations in filters
    Given I login as administrator
    And I go to System/Localization/Translations
    And I hide all columns in grid except Translated Value, Key
    And I sort grid by Key
    And I check "messages" in "Domain: All" filter strictly
    And I check "Yes" in "Translated: All" filter strictly
    When I check all records in grid
    And I click "Reset Translation" link from mass action dropdown
    Then I should see "Reset Confirmation"
    When I click "Reset" in modal window
    And I'm waiting for the translations to be reset
    And there is zero records in grid
    When I reset Domain filter
    Then the number of records greater than or equal to 1

  Scenario: Reset all translations
    Given I login as administrator
    And I go to System/Localization/Translations
    And I hide all columns in grid except Translated Value, Key
    And I sort grid by Key
    When I check all records in grid
    And I click "Reset Translation" link from mass action dropdown
    Then I should see "Reset Confirmation"
    When I click "Reset" in modal window
    And I'm waiting for the translations to be reset
    When I check "Yes" in "Translated: All" filter strictly
    And there is zero records in grid
