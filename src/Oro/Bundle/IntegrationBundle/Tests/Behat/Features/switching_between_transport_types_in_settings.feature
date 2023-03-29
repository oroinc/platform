@regression
@ticket-BAP-17677
@behat-test-env
Feature: Switching between transport types in settings
  In order to manage integrations having more than one transport type
  As an Administrator
  I need to be able to switch transport types on the Integration edit page

  Scenario: Switching between transport types in settings should reload the form for another transport type
    Given I login as administrator
    And I go to System/Integrations/Manage Integrations
    And I click "Create Integration"
    And I fill form with:
      | Type | Test Channel |
    Then "Integration Channel Form" must contains values:
      | Transport type    | Test Transport 1 |
      | Transport 1 Field |                  |
    And I fill form with:
      | Transport type    | Test Transport 2 |
    Then "Integration Channel Form" must contains values:
      | Transport type    | Test Transport 2 |
      | Transport 2 Field |                  |
    And I should not see "Transport 1 Field"
    When I save and close form
    Then I should see validation errors:
      | Name | This value should not be blank. |
    And I fill form with:
      | Name              | IntegrationName |
    When I save and close form
    Then I should see "Integration saved" flash message
    When I click edit "IntegrationName" in grid
    Then "Integration Channel Form" must contains values:
      | Name              | IntegrationName  |
      | Transport type    | Test Transport 2 |
      | Transport 2 Field |                  |
    And I should not see "Transport 1 Field"
    And I fill form with:
      | Transport type    | Test Transport 1 |
    Then "Integration Channel Form" must contains values:
      | Transport type    | Test Transport 1 |
      | Transport 1 Field |                  |
    And I should not see "Transport 2 Field"
    When I save and close form
    Then I should see "Integration saved" flash message
    When I click edit "IntegrationName" in grid
    Then "Integration Channel Form" must contains values:
      | Name              | IntegrationName  |
      | Transport type    | Test Transport 1 |
      | Transport 1 Field |                  |
    And I should not see "Transport 2 Field"
