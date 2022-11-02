@ticket-BB-16157
@fixture-OroUserBundle:user.yml
Feature: Reset password link expire

  Scenario: Feature Background
    Given sessions active:
      | Admin        | first_session  |
      | Unauthorized | second_session |

  Scenario: Reset password link must be expire after user login
    Given I proceed as the Unauthorized
    And I am on Login page
    And I click "Forgot your password?"
    And I fill form with:
      | Username or Email | admin |
    When I click "Request"
    And I follow "RESET PASSWORD" link from the email
    Then I should not see "Not Found"
    And I should be on User Password Reset page

    When I proceed as the Admin
    And I login as administrator
    And I proceed as the Unauthorized
    And I follow "RESET PASSWORD" link from the email
    Then I should see "Not Found"

  Scenario: Reset password link must be expire after user change his email
    Given I proceed as the Unauthorized
    And I am on Login page
    And I click "Forgot your password?"
    And I fill form with:
      | Username or Email | admin |
    When I click "Request"
    And I follow "RESET PASSWORD" link from the email
    Then I should not see "Not Found"
    And I should be on User Password Reset page

    Given I proceed as the Admin
    And I open User Profile Update page
    And I fill form with:
      | Primary Email | new-admin-email@example.com |
    When I save and close form
    Then I should see "User saved" flash message

    Given I proceed as the Unauthorized
    When I follow "RESET PASSWORD" link from the email
    Then I should see "Not Found"

  Scenario: Reset password link must be expire after user change his password
    Given I proceed as the Unauthorized
    And I am on Login page
    And I click "Forgot your password?"
    And I fill form with:
      | Username or Email | admin |
    When I click "Request"
    And I follow "RESET PASSWORD" link from the email
    Then I should not see "Not Found"
    And I should be on User Password Reset page

    Given I proceed as the Admin
    When I follow "More actions"
    And I follow "Change password"
    And I click "Suggest password"
    And I click "Save"
    Then I should see "Reset password email has been sent to user" flash message

    Given I proceed as the Unauthorized
    When I follow "RESET PASSWORD" link from the email
    Then I should see "Not Found"

  Scenario: Change email for another user not affected reset password link
    Given I proceed as the Unauthorized
    And I am on Login page
    And I click "Forgot your password?"
    And I fill form with:
      | Username or Email | charlie |
    When I click "Request"
    And I follow "RESET PASSWORD" link from the email
    Then I should not see "Not Found"
    And I should be on User Password Reset page

    Given I proceed as the Admin
    And I go to System/User Management/Users
    And I click edit charlie in grid
    When I fill form with:
      | Primary Email | new-charlie-email@example.com |
    And I save and close form
    Then I should see "User saved" flash message

    Given I proceed as the Unauthorized
    When I follow "RESET PASSWORD" link from the email
    Then I should see "Not Found"
