@regression
Feature: Notify assigned Sales Representatives
  As an Administrator
  When I create email notification for Contact Request
  I wan't select assigned Sales Reps from Contact Request to be notified.

  Scenario: Create email template
    Given I login as administrator
    And go to System/ Emails/ Templates
    And click "Create Email Template"
    And fill form with:
      |Owner         |John Doe       |
      |Template Name |Test Template  |
      |Type          |Plain Text     |
      |Entity Name   |Contact Request|
      |Subject       |Test Subject   |
    When I save and close form
    Then I should see "Template saved" flash message

  Scenario: Create notification rule
    Given go to System/ Emails/ Notification Rules
    And click "Create Notification Rule"
    And fill form with:
      |Entity Name |Contact Request |
    And I save and close form
    Then I should see "This value should not be blank."
    And fill form with:
      |Event Name  |Entity create   |
    And I save and close form
    Then I should see "This value should not be blank."
    And fill form with:
      |Template    |Test Template   |
      |Groups      |Administrators  |
    When I save and close form
    Then I should see "Notification Rule saved" flash message

  # TODO: unskip after BAP-13748
  @skip
  Scenario: Create contact request
    Given go to Activities/ Contact Requests
    And click "Create Contact Request"
    And fill form with:
      |First Name              | Test         |
      |Last Name               | Testerson    |
      |Prefered contact method | Email        |
      |Email                   |test@test.com |
      |Comment                 |Test comment  |
    When I save and close form
    Then I should see "Contact request has been saved successfully" flash message
    And I should receive email with:
      | Subject  | Test Subject   |
      | DateSent | <Date:today>   |
      | Receiver | admin@test.com |
