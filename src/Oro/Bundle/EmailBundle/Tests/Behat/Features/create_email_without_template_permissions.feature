@fixture-OroUserBundle:user.yml
Feature: Create email without template permissions
  In order to ensure email templates can be used only by users who have permissions
  As a user
  I need to be able to create email but if I do not have email template permissions I can not choose email templates

  Scenario: Disable template permissions for Sales Rep role
    Given I login as administrator
    And I go to System/User Management/Roles
    And I filter Label as is equal to "Sales Rep"
    When I click Edit Sales Rep in grid
    And select following permissions:
      | Email Template | View:None | Create:None | Edit:None | Delete:None |
    And I save and close form
    Then I should see "Role saved" flash message

  Scenario: Create new email without template permissions
    Given I login as "charlie" user
    And I click My Emails in user menu
    When I follow "Compose"
    And I should not see "Apply template"
    And fill "Email Form" with:
      | Body    | Create new email without template permissions |
      | To      | Charlie Sheen                                 |
      | Subject | Behat test                                    |
    And click "Send"
    Then I should see "The email was sent" flash message
