@ticket-BAP-16290
@fixture-OroUserBundle:user.yml

Feature: Send email form
  In order to have ability to send email
  As Administrator
  I need to be able to send email from admin panel

  Scenario: Check form validation when send form without email
    Given I login as administrator
    And I click My Emails in user menu
    And there is no records in grid
    When I follow "Compose"
    And fill "Email Form" with:
      | Body | This is very simple test mail |
    And press "Send"
    Then I should see "Email Form" validation errors:
      | ToField | This value contains not valid email address. |
      | Subject | This value should not be blank.              |
    And I close ui dialog

  Scenario: Create email template
    Given I go to System/ Emails/ Templates
    When I click "Create Email Template"
    And fill form with:
      | Template Name | test_user_template    |
      | Type          | Html                  |
      | Entity Name   | User                  |
      | Subject       | Test Template Subject |
      | Content       | Test Template Body    |
    And I save and close form
    Then I should see "Template saved" flash message

  Scenario: Send email with email template
    Given I click My Emails in user menu
    And there is no records in grid
    When I follow "Compose"
    And I fill "Email Form" with:
      | Body           | This is simple test mail |
      | To             | Charlie Sheen            |
      | Subject        | Behat test               |
      | Apply template | test_user_template       |
    And I click "Yes, Proceed"
    And "Email Form" must contains values:
      | Subject | Test Template Subject |
      | Body    | Test Template Body    |
    And press "Send"
    Then I should see "The email was sent" flash message
    Then I should see following grid:
      | CONTACT       | SUBJECT                                  |
      | Charlie Sheen | Test Template Subject Test Template Body |
