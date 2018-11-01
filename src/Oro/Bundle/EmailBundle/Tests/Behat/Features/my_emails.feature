@ticket-BAP-15471
@fixture-OroEmailBundle:my-emails.yml
Feature: My emails 
  In order to get access to my emails  
  As an administrator
  I need to CRUD emails in backend office

  Scenario: Filter emails
    Given I login as administrator
      And I click My Emails in user menu
    When I follow "Compose"
      And fill "Email Form" with:
      | Body    | This is very simple test mail |
      | To      | Charlie Sheen                 |
      | Subject | Behat test                    |
      And click "Send"
    Then I should see "The email was sent" flash message

  Scenario: Filter emails
    When I filter "Date/Time" as between "2010-10-30" and "2010-11-01"
    Then I should not see alert
      And I should see following grid:
        | Subject               | Date        |
        | There is no spoon     | 2010-10-31 |
      And I should see "Total Of 1 Records"

  Scenario: Filter emails by From field
    Given I login as administrator
    And I click My Emails in user menu
    When I filter From as contains "Charlie"
      And I should see following grid:
        | Subject               | Date        |
        | There is no spoon     | 2010-10-31 |
      And I should see "Total Of 1 Records"
