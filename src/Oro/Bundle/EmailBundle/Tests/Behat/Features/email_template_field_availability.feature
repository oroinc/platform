@regression
@fixture-OroUserBundle:users.yml

Feature: Email template field availability

  Scenario: Feature background
    Given sessions active:
      | Admin  | first_session  |
      | Admin1 | second_session |

  Scenario: Check that the entity field without available_in_template=true is disallowed in the email template
    Given I proceed as the Admin
    And I login as administrator
    When I go to System/ Emails/ Templates
    And I click "Create Email Template"
    And I fill form with:
      | Template Name | Test Disallowed Field                        |
      | Entity Name   | User                                         |
      | Subject       | Test Subject                                 |
      | Content       | User password expires: {{ entity.password }} |
    And I save and close form
    Then I should see only "The template in Content field (English (United States)) accesses a disallowed property \"password\" on \"entity\" variable." error message

  Scenario: Check that oro_config_value() is disallowed in the email template
    When I go to System/ Emails/ Templates
    And I click "Create Email Template"
    And I fill form with:
      | Template Name | Test Disallowed Function                                       |
      | Entity Name   | User                                                           |
      | Subject       | Test Subject                                                   |
      | Content       | System name: {{ oro_config_value('oro_ui.application_name') }} |
    And I save and close form
    Then I should see only "The template in Content field (English (United States)) calls a disallowed Twig function \"oro_config_value\"." error message

  Scenario: Check that email template with allowed fields works correctly
    Given I proceed as the Admin
    And I go to System/ Emails/ Templates
    And I click "Create Email Template"
    And I fill form with:
      | Template Name | Test Allowed Fields                                                                           |
      | Entity Name   | User                                                                                          |
      | Subject       | Hello {{ entity.firstName }}                                                                  |
      | Content       | Username: {{ entity.username }}, Email: {{ entity.email }}, FirstName: {{ entity.firstName }} |
    And I save form
    Then I should see "Template saved" flash message

    Given I proceed as the Admin1
    And I login as administrator
    When go to System/ User Management/ Users
    And click view "admin@example.com" in grid
    And click "More actions"
    And click "Send email"
    And I fill "Send Email Form" with:
      | To             | admin@example.com   |
      | Apply template | Test Allowed Fields |
    And I click "Yes, Proceed" in confirmation dialogue
    Then "Email Form" must contains values:
      | Subject | Hello John |
    When I click "Send"
    Then I should see "The email was sent" flash message
    And email with Subject "Hello John" should contain the following "Username: admin, Email: admin@example.com, FirstName: John"

  Scenario: Check that making a field unavailable in email templates does not block the sending pipeline
    Given I proceed as the Admin
    When I go to System/ Entities/ Entity Management
    And I filter Name as is equal to "User"
    And I click view User in grid
    And I click edit firstName in grid
    And I fill form with:
      | Available in email templates | No |
    And I save and close form
    Then I should see "Field saved" flash message

    Given I proceed as the Admin1
    When I click "More actions"
    And I click "Send email"
    And I fill "Send Email Form" with:
      | To             | admin@example.com   |
      | Apply template | Test Allowed Fields |
    And I click "Yes, Proceed" in confirmation dialogue
    Then "Email Form" must contains values:
      | Subject | Hello N/A |
    When I click "Send"
    Then I should see "The email was sent" flash message
    And email with Subject "Hello N/A" should contain the following "Username: admin, Email: admin@example.com, FirstName: N/A"

  Scenario: Check that entity is not available when creating an email template if setting available_in_template=false
    Given I proceed as the Admin
    And I click "Edit"
    And I fill form with:
      | Available in email templates | No |
    And I save and close form
    Then I should see "Entity saved" flash message

    When I go to System/ Emails/ Templates
    And I click "Create Email Template"
    Then I should not see the following options for "Entity Name" select pre-filled with "User":
      | User |
