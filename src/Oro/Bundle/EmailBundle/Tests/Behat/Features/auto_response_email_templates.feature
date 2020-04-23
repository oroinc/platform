@regression
@ticket-BB-19178
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
