@ticket-BAP-17151
@fixture-OroUserBundle:user_with_expired_reset_password_token.yml

Feature: Expired reset password token regeneration
  In order to reset password
  As user
  I should be able to regenerate expired reset password token

  Scenario: Expired reset password token regeneration
    Given I am on Login page
    And I click "Forgot your password?"
    And I fill form with:
      | Username or Email | charlie@example.com |
    And I confirm reset password
    Then I should see "If there is a user account associated with ...@example.com you will receive an email with a link to reset your password."
    And Email should contains the following:
      | To      | charlie@example.com |
      | Subject | Reset password      |
    And Email should not contains the following:
      | Body    | testConfirmationToken |
