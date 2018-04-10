@ticket-BAP-13987
@fixture-OroEmailBundle:OtherUser`sEmailsDisplayedInGridInMy EmailsPage.yml

Feature: Other users emails displayed in grid in My Emails page
  In order to check ACL for the emails for Sales Rep role
  As an Administrator
  I send email by one user and check that another user didn't see it

  Scenario: Create different window session
    Given sessions active:
      | User1  |first_session |
      | User2  |second_session|

  Scenario: Create email with User1
    Given I proceed as the User1
    And login as "Charlie1@example.com" user
    And click My Emails in user menu
    And click "Compose"
    And I fill form with:
    | To      | John Doe       |
    | Subject | Test mail      |
    | Body    | Test mail body |
    When I click "Send"
    Then I should see "The email was sent" flash message
    And should see following grid:
    | Contact  | Subject                  |
    | John Doe | Test mail Test mail body |

  Scenario: Check email visibility for other users
    Given I proceed as the User2
    And login as "Samantha1@example.com" user
    When click My Emails in user menu
    Then I should not see "John Doe"
