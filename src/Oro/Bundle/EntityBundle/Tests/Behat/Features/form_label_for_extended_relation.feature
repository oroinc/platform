@regression
@ticket-BAP-16241

Feature: Form label for extended relation
  In order to be able to set required field label value
  As ORO configurator
  I want to be sure that set value displayed correctly

  Scenario: Check correctness of relation field label
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And I filter Name as Contains "CalendarEvent"
    And I click view Calendar event in grid
    And I click "Create Field"
    When I fill form with:
      | Field name   | event_user   |
      | Storage Type | Table column |
      | Type         | Many to one  |
    And I click "Continue"
    And I fill form with:
      | Label         | Event Assigned User |
      | Target Entity | User                |
      | Target Field  | Username            |
      | Show On Form  | Yes                 |
    And I save and close form
    And I click update schema
    And I go to Activities/ Calendar Events
    And I click "Create Calendar event"
    And I fill form with:
      | Title               | Event with User |
      | Event Assigned User | John Doe        |
    And I save and close form
    Then I should see "Event Assigned User admin"
