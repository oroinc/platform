@regression
@ticket-BAP-13807
@automatically-ticket-tagged
Feature: Managing users roles
  In order to control user permissions
  As an Administrator
  I want to be able to manage user roles

  Scenario: User role create
    Given I login as administrator
    And go to System/User Management/Roles
    When I click "Create Role"
    And I save and close form
    Then I should see validation errors:
      | Role      | This value should not be blank.  |
    And I fill form with:
      | Role        | Test role              |
      | Description | Hello it's description |
    And select following permissions:
      | Call        | View:Division      | Create:Business Unit | Edit:User | Delete:User | Assign:User |
      | Lead        | View:Division      | Create:User          | Edit:User | Delete:User | Assign:User |
      | Language    | View:Business Unit | Create:User          | Edit:User | Assign:User | Translate:User |
      | Task        | View:Division      | Create:Business Unit | Edit:User | Delete:User | Assign:User |
    And I check "Access system information" entity permission
    When I save and close form
    Then I should see "Test role"
    And the role has following active permissions:
      | Call        | View:Division      | Create:Business Unit | Edit:User | Delete:User | Assign:User |
      | Lead        | View:Division      | Create:User          | Edit:User | Delete:User | Assign:User |
      | Language    | View:Business Unit | Create:User          | Edit:User | Assign:User | Translate:User |
      | Task        | View:Division      | Create:Business Unit | Edit:User | Delete:User | Assign:User |
    And following capability permissions should be checked:
      | Access system information |

  Scenario: Edit user role
    Given I click "Edit"
    When I fill form with:
      | Role        | Edited test role              |
      | Description | Hello it's edited description |
    And select following permissions:
      | Contact        | View:Business Unit | Create:Business Unit | Edit:Division | Delete:Division      | Assign:User     |
      | Email Campaign | View:Global        | Create:Division      | Edit:Global   | Assign:Division      | Assign:Division |
      | Task           | View:User          | Create:Business Unit | Edit:User     | Delete:User          | Assign:User     |
    And I check "Access system information" entity permission
    And I check "Access job queue" entity permission
    And I check "Access entity management" entity permission
    When I save and close form
    Then I should see "Edited test role"
    And the role has following active permissions:
      | Contact        | View:Business Unit | Create:Business Unit | Edit:Division | Delete:Division      | Assign:User     |
      | Email Campaign | View:Global        | Create:Division      | Edit:Global   | Assign:Division      | Assign:Division |
      | Task           | View:User          | Create:Business Unit | Edit:User     | Delete:User          | Assign:User     |
      | Call           | View:Division      | Create:Business Unit | Edit:User     | Delete:User       | Assign:User    |
      | Lead           | View:Division      | Create:User          | Edit:User     | Delete:User       | Assign:User    |
      | Language       | View:Business Unit | Create:User          | Edit:User     | Assign:User       | Translate:User |
    And following capability permissions should be checked:
      | Access system information       |
      | Access job queue                |
      | Access entity management        |

  Scenario: Disable configuring permissions and capabilities for user role
    And I go to System/User Management/Roles
    When I click View Edited test role in grid
    Then the role has following active permissions:
      | Email-User Relation | View:None | Create:None | Edit:None |
    And I should see "Manage passwords"
    When permission VIEW for entity Oro\Bundle\EmailBundle\Entity\EmailUser and group default restricts in application
    And permission CREATE for entity Oro\Bundle\EmailBundle\Entity\EmailUser and group default restricts in application
    And capability password_management and group default restricts in application
    And I reload the page
    Then the role has not following permissions:
      | Email-User Relation | View | Create |
    And I should not see "Manage passwords"
    When all permissions for entity Oro\Bundle\EmailBundle\Entity\EmailUser and group default restricts in application
    And I should see "Email-User Relation"
    And I reload the page
    Then I should not see "Email-User Relation"

  Scenario: Delete user role
    Given I go to System/User Management/Roles
    Then I should see Edited test role in grid
    And I keep in mind number of records in list
    When I click Delete Edited test role in grid
    And I confirm deletion
    Then the number of records decreased by 1
    And I should not see "Edited test role"
