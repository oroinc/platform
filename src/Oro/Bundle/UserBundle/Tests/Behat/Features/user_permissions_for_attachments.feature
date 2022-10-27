@ticket-BAP-18637
@fixture-OroUserBundle:user_permissions_for_attachments.yml

Feature: User permissions for attachments
  In order to check that user avatar thumbnails can be accessed only by authorized users
  As an Administrator
  I create users and check if user can access to attachments of other user

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Add files and update permissions for users
    Given I proceed as the Admin
    And login as administrator
    # Update Charlie avatar
    And I go to System/ User Management/ Users
    And I click edit Charlie in grid
    When I fill form with:
      | Avatar | cat0.jpg |
    And I save form
    Then I should see "User saved" flash message
    # Update Misty avatar
    And I go to System/ User Management/ Users
    And I click edit Misty in grid
    When I fill form with:
      | Avatar | cat0.jpg |
    And I save form
    Then I should see "User saved" flash message
    # Set user permission
    And I go to System/ User Management/ Roles
    And I click edit Administrator in grid
    When select following permissions:
      | User | View:Business Unit |
    And I save and close form
    Then I should see "Role Saved" flash message

  Scenario: Check permissions of user located in all business units
    Given I proceed as the Buyer
    When I login to dashboard as "charlie" user
    Then I should see avatar for user "charlie"
    And I should see avatar for user "misty"

  Scenario: Check permissions of user located in second business unit
    Given I proceed as the Buyer
    When I login to dashboard as "misty" user
    Then I should not see avatar for user "charlie"
    And I should see avatar for user "misty"
    And I click Logout in user menu

  Scenario: Check permissions of user located in third business unit
    Given I proceed as the Buyer
    When I login to dashboard as "samantha" user
    Then I should not see avatar for user "charlie"
    And I should not see avatar for user "misty"

  Scenario: Check anonymous user permissions
    Given I proceed as the Buyer
    When I click Logout in user menu
    Then I should not see avatar for user "charlie"
    And I should not see avatar for user "misty"
