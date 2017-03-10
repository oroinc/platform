Feature: Mass reset translations
  Scenario: Reset several translations
    Given I login as administrator
    When I go to System/Localization/Translations
    And I check first 2 records in grid
    And I click "Reset Translation" link from mass action dropdown
    Then I should see "Reset Confirmation"
    When I click "Reset" in modal window
    Then I should see "Selected translations were reset to their original values." flash message
    And I should see that Translated Value in 1 row is empty
    And I should see that Translated Value in 2 row is empty
    And I should see that Translated Value in 3 row is not empty
    And I click Logout in user menu
