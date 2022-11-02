@ticket-BAP-21097
@regression

Feature: Create email template with absolute link
  In order to add a custom link to any ORO page
  As Administrator
  I need to be able to add absolute links through insert link functionality to the email template

  Scenario: Create email template with absolute link
    Given I login as administrator
    And go to System/ Emails/ Templates
    And click "Create Email Template"
    And fill form with:
      | Owner         | John Doe                         |
      | Template Name | Test Template With Absolute Link |
      | Type          | Html                             |
      | Entity Name   | Email                            |
      | Subject       | Test Template With Absolute Link |
    And I fill in WYSIWYG "Email Template Default Content" with "<p>Hello User, please login through - <a class='oro_email_template_login_link' title='Login form' href='http://localhost/admin/user/login'>Login form</a></p>"
    When I save form
    Then I should see "Template saved" flash message
    When I click "Preview"
    Then I should see "Email Template Login Absolute Link" element inside "Preview Email" iframe
    And I close ui dialog

  Scenario: Create email template with absolute link via auto-response rule
    Given I go to System / Configuration
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    And I click "Add Mailbox"
    And I click "Add Rule"
    And I fill "Add Autoresponse Rule Form" with:
      | Name          | Rule 1                                                  |
      | Type          | html                                                    |
      | Subject       | Test Template With Absolute Link via auto-response rule |
    And I fill in WYSIWYG "Autoresponse Rule Default Content" with "<p>Hello User, please login through - <a class='oro_email_template_login_link' title='Login form' href='http://localhost/admin/user/login'>Login form</a></p>"
    And I click "Add"
    And go to System/ Emails/ Templates
    Then I should see alert with message "You have unsaved changes, are you sure you want to leave this page?"
    And I accept alert
    And I filter Template name as is equal to "Test Template With Absolute Link via auto-response rule"
    Then there are 1 records in grid
    When I click "Edit" on row "Test Template With Absolute Link via auto-response rule" in grid
    And I click "Preview"
    Then I should see "Email Template Login Absolute Link" element inside "Preview Email" iframe
    And I close ui dialog
