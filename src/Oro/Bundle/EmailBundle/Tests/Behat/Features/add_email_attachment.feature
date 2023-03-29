@regression
@ticket-BAP-19096
Feature: Add email attachment
  In order to send files with email
  As a user
  I want to be able to attach a file to an email

  Scenario: Email attachment MIME type validation
    Given I login as administrator
    And I click My Emails in user menu
    And I click "Compose"
    And I fill "Email Form" with:
      | To      | John Doe   |
      | Subject | Test MIME type email |
    When I attach "page.html" file to email
    Then I should see "page.html"
    When I click "Send"
    Then I should see "The MIME type of the file is invalid (\"text/html\"). Allowed MIME types are \"text/csv\", \"text/plain\", \"application/msword\", \"application/vnd.openxmlformats-officedocument.wordprocessingml.document\", \"application/vnd.ms-excel\", \"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet\", \"application/vnd.ms-powerpoint\", \"application/vnd.openxmlformats-officedocument.presentationml.presentation\", \"application/pdf\", \"application/zip\", \"image/gif\", \"image/jpeg\", \"image/png\", \"image/webp\"."
    And I close ui dialog

  Scenario: Email attachment max file size validation
    Given I go to System / Configuration
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    And uncheck "Use default" for "Maximum Attachment Size, Mb" field
    Then I fill in "Maximum Attachment Size, Mb" with "0.001"
    And I save form
    Then I should see "Configuration saved" flash message

    When I click My Emails in user menu
    And I click "Compose"
    And I fill "Email Form" with:
      | To      | John Doe   |
      | Subject | Test Max Size email |
    And I attach "attachment1.png" file to email
    Then I should see "attachment1.png"
    When I click "Send"
    Then I should see "The file is too large (22588 bytes). Allowed maximum size is 1048 bytes."
    When I click "Send"
    Then I should see "The file is too large (22588 bytes). Allowed maximum size is 1048 bytes."
    And I close ui dialog

  Scenario: Attach a file to email
    Given I go to System / Configuration
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    And check "Use default" for "Maximum Attachment Size, Mb" field
    And I save form
    Then I should see "Configuration saved" flash message

    When I click My Emails in user menu
    And I click "Compose"
    And I fill "Email Form" with:
      | To      | John Doe   |
      | Subject | Test email |
    And I attach "attachment1.png" file to email
    Then I should see "attachment1.png"
    When I click "Send"
    Then I should see "The email was sent" flash message
    When I click on Test email in grid
    Then I should see "1 Attachment"
    And I should see " atta..1.png"

  Scenario: Attach a file to email's reply
    When I click "Reply"
    And I attach "attachment2.png" file to email
    Then I should see "attachment2.png"
    When I click "Send"
    Then I should see "The email was sent" flash message
    Then I should see "1 Attachment"
    And I should see " atta..2.png"
