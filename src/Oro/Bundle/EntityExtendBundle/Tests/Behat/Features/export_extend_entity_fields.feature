@regression
@ticket-BAP-20922

Feature: Export extend entity fields
  Сheck if the select and multi select fields are exported without problems with html symbols

  Scenario: Feature background
    Given I login as administrator
    And go to System/Entities/Entity Management
    And filter Name as is equal to "Call"
    And click View Call in grid

  Scenario Outline: Create Select and Multi-Select fields
    Given I click on "Create Field"
    When I fill form with:
      | Field name | <Name> |
      | Type       | <Type> |
    And click "Continue"
    And fill form with:
      | Label | <Label> |
    And set Options with:
      | Label            |
      | Carte d'identité |
    And save and close form
    Then I should see "Field saved" flash message

    Examples:
      | Name               | Type         | Label              |
      | select_field       | Select       | Select field       |
      | multi_select_field | Multi-Select | Multi select field |

  Scenario: Update schema
    Given I click update schema

  Scenario: Create a call record in the Calls grid
    Given go to Activities/ Calls
    And click "Log call"
    When I fill "Log Call Form" with:
      | Subject             | Call to Someone                           |
      | Additional comments | Offered $40 discount on her next purchase |
      | Call date & time    | <DateTime:2016-10-31 08:00:00>            |
      | Phone number        | 0501468825                                |
      | Direction           | Outgoing                                  |
      | Duration            | 60s                                       |
      | Select Field        | Carte d'identité                          |
    And check "Carte d'identité"
    And save and close form
    Then should see "Call saved" flash message

  Scenario: Export call and check fields
    Given go to Activities/ Calls
    And should see "Export Grid"
    When I click "Export Grid"
    And click "CSV"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Grid export performed successfully. Download" text
    And exported file contains at least the following columns:
      | Subject         | Phone number | Call date & time    | Contexts | Select field     | Multi select field |
      | Call to Someone | 0501468825   | 10/31/2016 08:00:00 | John Doe | Carte d'identité | Carte d'identité   |
