@regression
@ticket-BAP-19096
Feature: Add email attachment
  In order to send files with email
  As a user
  I want to be able to attach a file to an email

  Scenario: Attach a file to email
    Given I login as administrator
    And I click My Emails in user menu
    And I click "Compose"
    And I fill "Email Form" with:
      | To      | John Doe   |
      | Subject | Test email |
    When I attach "attachment1.txt" file to email
    Then I should see "attachment1.txt"
    When I click "Send"
    Then I should see "The email was sent" flash message
    When I click on Test email in grid
    Then I should see "1 Attachment"
    And I should see " atta..1.txt"

  Scenario: Attach a file to email's reply
    When I click "Reply"
    And I attach "attachment2.txt" file to email
    Then I should see "attachment2.txt"
    When I click "Send"
    Then I should see "The email was sent" flash message
    Then I should see "1 Attachment"
    And I should see " atta..2.txt"
