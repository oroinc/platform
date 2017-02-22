Feature: User statuses
  In order to allow or deny users to login
  As Administrator
  I need to change User statuses

  Scenario: Make User unavailable for login
    Given Charlie Sheen active user exists in the system
    And I login as administrator
    When I make charlie account disabled
    Then Charlie Sheen user has no possibility to login to the Dashboard

  Scenario: Make User available for login
    When I make charlie account enabled
    Then Charlie Sheen user could login to the Dashboard
