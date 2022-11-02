@fixture-OroNotificationBundle:NotificationAlertsFixture.yml

Feature: Notification Alerts Grid operations
  In order to manage notifications alerts, e.g. display, filter, delete, mass-delete, export
  As an Administrator
  I should be able to use notifications alerts grids and perform certain operations with it

  Scenario: Check notifications alerts grid
    Given I login as administrator
    When I go to System/Alerts
    Then there are 9 records in grid

  Scenario: Check notifications alerts grid "Operation" filter
    When I set filter "Operation" as is equal to "import" and press Enter key
    Then there is 6 record in grid
    When I set filter "Operation" as is equal to "export" and press Enter key
    Then there is 3 record in grid
    And I reset "Operation" filter

  Scenario: Check notifications alerts grid "Alert Type" filter
    When I set filter "Alert Type" as is equal to "auth" and press Enter key
    Then there is 3 record in grid
    When I set filter "Alert Type" as is equal to "sync" and press Enter key
    Then there is 6 record in grid
    And I reset "Alert Type" filter

  Scenario: Check notifications alerts grid "Resource" filter
    When I set filter "Resource" as is equal to "calendar" and press Enter key
    Then there is 6 record in grid
    When I set filter "Resource" as is equal to "tasks" and press Enter key
    Then there is 3 record in grid
    And I reset "Resource" filter

  Scenario: Check notifications alerts grid "Step" filter
    When I set filter "Step" as is equal to "get" and press Enter key
    Then there is 3 record in grid
    When I set filter "Step" as is equal to "map" and press Enter key
    Then there is 3 record in grid
    When I set filter "Step" as is equal to "save" and press Enter key
    Then there is 3 record in grid
    And I reset "Step" filter

  Scenario: Check notifications alerts grid "Item ID" filter
    When I filter Item ID as is empty
    Then there is 3 record in grid
    When I filter Item ID as is not empty
    Then there is 6 record in grid
    And I reset "Item ID" filter

  Scenario: Check notifications alerts grid "External ID" filter
    When I filter External ID as is empty
    Then there is 3 record in grid
    When I filter External ID as is not empty
    Then there is 6 record in grid
    And I reset "External ID" filter

  Scenario: Check notifications alerts grid multiple filters
    When I set filter "Operation" as is equal to "import" and press Enter key
    And I set filter "Alert Type" as is equal to "sync" and press Enter key
    And I set filter "Resource" as is equal to "tasks" and press Enter key
    And I filter Item ID as is not empty
    And I filter External ID as is empty
    Then there is 3 record in grid
    And I click "Reset"

  Scenario: Check notifications alerts grid mass delete action
    Given I set filter "Resource" as is equal to "calendar" and press Enter key
    When I check all records in grid
    And click Delete mass action
    And confirm deletion
    And I reset "Resource" filter
    Then there is 3 records in grid

  Scenario: Check notifications alerts grid record delete action
    When I click delete "118" in grid
    And I confirm deletion
    And I should see "Notification Alert deleted" flash message
    Then there is 2 records in grid
    When I click delete "117" in grid
    And I confirm deletion
    And I should see "Notification Alert deleted" flash message
    Then there is 1 records in grid
    When I click delete "116" in grid
    And I confirm deletion
    And I should see "Notification Alert deleted" flash message
    Then there is no records in grid
