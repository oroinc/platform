@ticket-BAP-21510

Feature: Crud notification rule
  In Order to manage Email notification rules
  As an Administrator
  I want to be able to use entities

  Scenario: Feature background
    Given I login as administrator

  Scenario: Create an Email notification rule
    Given I go to System/Emails/Notification Rules
    And I click "Create Notification Rule"
    And I fill form with:
      | Entity Name | User                |
      | Event Name  | Entity create       |
      | Template    | authentication_code |
      | Email       | test@example.com    |
      | Groups      | Administrators      |
    And I save and close form
    Then I should see "Email notification rule saved" flash message
    And I should see following grid containing rows:
      | Entity Name | Event Name    | Template            | Recipient email  |
      | User        | Entity create | authentication_code | test@example.com |

  Scenario: Administrator checks state of previously created notification rule
    When I filter Template as is equal to "authentication_code"
    And I click edit "User" in grid
    And I fill form with:
      | Event Name | Entity update |
    And I save and close form
    Then I should see "Email notification rule saved" flash message
    And I should see following grid containing rows:
      | Entity Name | Event Name    | Template            | Recipient email  |
      | User        | Entity update | authentication_code | test@example.com |

  Scenario: Administrator checks state of previously created notification rule
    When I click delete "User" in grid
    And I confirm deletion
    Then I should see "Item deleted" flash message
    And there is no records in grid
