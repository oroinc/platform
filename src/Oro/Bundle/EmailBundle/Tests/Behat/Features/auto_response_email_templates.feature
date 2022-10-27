@regression
@ticket-BB-19178
@ticket-BB-19252
@fixture-OroEmailBundle:templates.yml
@fixture-OroEmailBundle:autoresponse-templates.yml

Feature: Auto response email templates
  In order to be able to manage auto response rules
  As an administrator
  I want to be able to choose only valid Response Templates

  Scenario: Check Response Templates list
    Given I login as administrator
    And I go to System / Configuration
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    And I click "Add Mailbox"
    When I click "Add Rule"
    Then should see the following options for "Response Template" select in form "Add Autoresponse Rule Form":
      | email_entity_template                     |
      | auto_response_non_entity_related_template |
    And should not see the following options for "Response Template" select in form "Add Autoresponse Rule Form":
      | test_template      |
      | not_system_email_1 |
      | not_system_email_2 |
      | not_system_email_3 |
      | system_email       |
      | non_entity_related |

  Scenario: Check Autoresponse Rule creation with existing template
    When I fill "Add Autoresponse Rule Form" with:
      | Name              | Rule 1                |
      | Response Template | email_entity_template |
    And I click "Add"
    Then I should see following "Autoresponse Rules Grid" grid:
      | Name   |
      | Rule 1 |

  Scenario: Check Autoresponse Rule Default Subject validation
    When I click "Add Rule"
    And I fill "Add Autoresponse Rule Form" with:
      | Name | Rule invalid |
    And I click "Add"
    Then I should see "Add Autoresponse Rule Form" validation errors:
      | Default Subject | This value should not be blank. |
    And I close ui dialog

  Scenario: Check Autoresponse Rule English Subject validation
    When I click "Add Rule"
    And I fill "Add Autoresponse Rule Form" with:
      | Name            | Rule invalid |
      | Default Subject | Test subject |
    And I click "English"
    And I fill "Add Autoresponse Rule Form" with:
      | English Subject Fallback | false |
      | English Subject          |       |
    And I click "Add"
    And I click "English"
    Then I should see "Add Autoresponse Rule Form" validation errors:
      | English Subject | This value should not be blank. |
    And I close ui dialog

  Scenario: Check Autoresponse Rule creation with existing template
    When I click "Add Rule"
    And I fill "Add Autoresponse Rule Form" with:
      | Name            | Rule 2       |
      | Default Subject | Test subject |
    And I click "Add"
    Then I should see following "Autoresponse Rules Grid" grid:
      | Name   |
      | Rule 1 |
      | Rule 2 |
