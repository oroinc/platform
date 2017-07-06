@ticket-BAP-11239
@automatically-ticket-tagged
Feature: My emails widget
  I order to have quick notifications and quick access about new emails
  As a crm user
  I need to have email widget in toolbar

  Scenario: No emails
    Given I login as administrator
    When I click on email notification icon
    Then I should see "You don't have any emails yet"

  Scenario: New emails
    Given I receive new emails
    When reload the page
    Then email notification icon show 5 emails
    Then I click on email notification icon
    And I should see 4 emails in email list
    And all emails in email list must be new

  Scenario: View email
    Given I click on "Merry Christmas" email title
    And I should be on Email View page
    When I click on email notification icon
    Then 3 emails in email list must be new

  Scenario: Mark email as unread
    Given I mark "Merry Christmas" email as unread
    Then 4 emails in email list must be new

  Scenario: Click action link
    Given follow "Reply All"
    Then I should see an "Email Form" element
    And "Email Form" must contains values:
      | From    | "John Doe" <admin@example.com>        |
      | ToField | ["Charlie Sheen" <charlie@sheen.com>] |
      | Subject | Re: Merry Christmas                   |
