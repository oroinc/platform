@regression
@ticket-BB-27857
@fixture-OroEmailBundle:email_template_unavailable_field_rendering.yml

Feature: Email template unavailable field rendering
  In order to keep a user field that is not available in email templates out of outgoing email
  As an administrator
  I need such a field to be unusable when a template is written and to render a fallback when a template is sent

  Scenario: Creating an email template that reads a field unavailable in templates is refused
    Given I login as administrator
    When I go to System/ Emails/ Templates
    And I click "Create Email Template"
    And I fill form with:
      | Template Name | Unavailable Field Probe                              |
      | Entity Name   | User                                                 |
      | Subject       | Unavailable field probe                              |
      | Content       | Value start {{ entity.confirmationToken }} value end |
    And I save and close form
    Then I should see only "The template in Content field (English (United States)) accesses a disallowed property \"confirmationToken\" on \"entity\" variable." error message

  Scenario: A field unavailable in email templates cannot be enabled from entity management
    When I go to System/ Entities/ Entity Management
    And I filter Name as is equal to "User"
    And I click view User in grid
    And I click edit confirmationToken in grid
    Then the "Available in email templates" field should be disabled

  Scenario: Sending an email from a template that reads an undefined parameter renders the fallback
    Given I click Logout in user menu
    And I login as "charlie" user
    When I go to System/ User Management/ Users
    And click view "victor@example.com" in grid
    And I click "More actions"
    And I click "Send email"
    And I fill "Email Form" with:
      | To             | Charlie Sheen       |
      | Apply template | undefined_parameter |
    And I click "Yes, Proceed" in confirmation dialogue
    And I click "Send"
    Then I should see "The email was sent" flash message
    And Email should contains the following:
      | To      | charlie@example.com          |
      | Subject | Undefined parameter template |
      | Body    | Value start N_A value end    |
    And Email should not contains the following:
      | Body | behatstoredvalue |
