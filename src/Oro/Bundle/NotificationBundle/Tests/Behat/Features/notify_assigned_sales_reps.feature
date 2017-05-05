Feature: Notify assigned Sales Representatives
  As an Administrator
  When I create email notification for Contact Request
  I wan't select assigned Sales Reps from Contact Request to be notified.

  Scenario: Create email template
    Given I login as administrator
    And go to System/ Emails/ Templates
    And press "Create Email Tempate"
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
    And press "Create Notification Rule"
    And fill form with:
      |Entity Name |Contact Request |
      |Event Name  |Entity Create   |
      |Template    |Test Template   |
      |Groups      |Administrators  |
    When I save and close form
    Then I should see "Notification Rule saved" flash message

  # TODO: unskip after BAP-13748
  @skip
  Scenario: Create contact request
    Given go to Activities/ Contact Requests
    And press "Create Contact Request"
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
