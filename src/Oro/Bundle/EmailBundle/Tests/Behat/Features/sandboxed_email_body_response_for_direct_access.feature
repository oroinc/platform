@regression
@ticket-BB-27954
@fixture-OroEmailBundle:email-body-with-script.yml

Feature: Sandboxed email body response for direct access
  As a back office user
  I want a script stored in an email body not to run when the email body URL is opened as a page
  So that the sender of a message cannot act with my session

  Scenario: Script stored in an email body does not run on the email body page
    Given I login as administrator
    When I open the body of the "Email body with script" email as a page
    Then I should see "not executed"
    And I should not see "script executed"

  Scenario: Email view page still renders the message body
    Given I am on dashboard
    When I click My Emails in user menu
    And I click on Email body with script in grid
    Then I should see "not executed" inside "Email body" iframe
