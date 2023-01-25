@ticket-BB-20969
@fixture-OroUserBundle:user.yml

Feature: Reset user password
  In order to manage user's accounts
  As an Administrator
  I need to be able to reset user's passwords

  Scenario: Feature Background
    Given sessions active:
      | Admin        | first_session  |
      | Unauthorized | second_session |

  Scenario: Ensure user can log in
    Given I proceed as the Unauthorized
    And I am on Login page
    When I fill "Login Form" with:
      | Username | charlie |
      | Password | charlie |
    And I click "Log in"
    Then I should be on Admin Dashboard page
    And I am logged out

  Scenario: Reset user password from the admin panel
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/User Management/Users
    When I click "Reset password" on row "charlie" in grid
    Then should see "User charlie will receive the email to reset password and will be disabled from login." in confirmation dialogue
    When I click "Reset" in confirmation dialogue
    Then I should see "Password reset request has been sent to charlie@example.com." flash message
    And Email should contains the following:
      | Subject | Please reset your password                                               |
      | To      | charlie@example.com                                                      |
      | Body    | Hi, Charlie Sheen!                                                       |
      | Body    | The administrator has requested a password reset for your user profile.  |
    And I remember "RESET PASSWORD" link from the email
    And I should see charlie in grid with following data:
      | Password | Reset |

  Scenario: Check that user cannot log in
    Given I proceed as the Unauthorized
    And I am on Login page
    When I fill "Login Form" with:
      | Username | charlie |
      | Password | charlie |
    And I click "Log in"
    Then I should see "Your login was unsuccessful"

  Scenario: Reset password by emails link
    Given I proceed as the Unauthorized
    And I follow remembered "RESET PASSWORD" link from the email
    When I fill "User Reset Password Form" with:
      | New password    | charlieSheEn12358 |
      | Repeat password | charlieSheEn12358 |
    And click "Reset"
    Then I should see "Your password was successfully reset. You may log in now."

  Scenario: Login with new password
    Given I proceed as the Unauthorized
    And I am on Login page
    When I fill "Login Form" with:
      | Username | charlie |
      | Password | charlieSheEn12358 |
    And I click "Log in"
    Then I should be on Admin Dashboard page
