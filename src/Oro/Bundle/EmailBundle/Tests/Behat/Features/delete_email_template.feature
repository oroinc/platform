@fixture-OroEmailBundle:templates.yml
Feature: Delete email template
  As Administrator
  I need to be able to delete email template if it not system

  Scenario: Delete "non-system" email template from grid page
    Given I login as administrator
    And go to System/ Emails/ Templates
    And I filter Template Name as contains "not_system_email_"
    And I click "Delete" on first row in grid
    And confirm deletion
    Then I should see "Item deleted" flash message

  Scenario: Delete "non-system" email template from grid page using mass action deletion
    Given I check first 1 records in "Email Templates Grid"
    When I click "Delete" link from select all mass action dropdown in "Email Templates Grid"
    And confirm deletion
    Then I should see "One entity was deleted" flash message

  Scenario: Delete "non-system" email template from view page
    Given I click "Edit" on first row in grid
    When I click "Delete"
    And confirm deletion
    And I filter Template Name as contains "not_system_email_"
    Then I should see no records in "Email Templates Grid" table

  Scenario: Delete "system" email template from grid page
    Given I filter Template Name as contains "system_email"
    Then I should not see following actions for system_email in grid:
      | Delete |

  Scenario: Delete "system" email template from grid page using mass action deletion
    Given I check first 1 records in "Email Templates Grid"
    When I click "Delete" link from select all mass action dropdown in "Email Templates Grid"
    And confirm deletion
    Then I should see "No entities were deleted" flash message

  Scenario: Delete "system" email template from view page
    Given I click "Edit" on first row in grid
    Then I should not see following buttons:
      | Delete |
