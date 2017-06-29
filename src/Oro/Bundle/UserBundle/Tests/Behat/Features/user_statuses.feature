Feature: User statuses
  In order to allow or deny users to login
  As Administrator
  I need to change User statuses

  Scenario: Make User unavailable for login
    Given Charlie Sheen active user exists in the system
    And I login as administrator
    And I go to System/User Management/Users
    When I click Disable charlie in grid
    Then Charlie Sheen user has no possibility to login to the Dashboard

  Scenario: Make User available for login
    When I check "All" in Enabled filter
    And I click Enable charlie in grid
    Then Charlie Sheen user could login to the Dashboard

  Scenario: Disable users mass action doesn't affect current logged in user
    And check all records in grid
    And I click "Disable" link from mass action dropdown
    And I press "Apply"
    Then I should see "admin@example.com" in grid with following data:
      |Username   |admin  |
      |Enabled    |Enabled|
    And I should see "charlie@example.com" in grid with following data:
      |Username   |charlie |
      |Enabled    |Disabled|
