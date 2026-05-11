@ticket-BAP-13807
@ticket-BAP-21510
@automatically-ticket-tagged

Feature: Managing users roles
  In order to control user permissions
  As an Administrator
  I want to be able to manage user roles

  Scenario: Sort users grid on Create Role page
    Given the following users:
      | firstName | lastName | email              | username | authStatus         |
      | Wendy     | Williams | wendy@williams.com | wendy    | @admin->authStatus |
      | Brad      | Pitt     | brad@pitt.com      | brad     | @admin->authStatus |
    And I login as administrator
    And go to System/User Management/Roles
    When I click "Create Role"

    And I sort "Role Users Grid" by "Has role"
    Then I should see following "Role Users Grid" grid containing rows:
      | Username |
      | admin    |
    When I reset "Role Users Grid" grid

    And I sort "Role Users Grid" by "First Name"
    Then I should see following "Role Users Grid" grid containing rows:
      | First Name |
      | Brad       |
      | John       |
      | Wendy      |
    When I reset "Role Users Grid" grid

    And I sort "Role Users Grid" by "Last Name"
    Then I should see following "Role Users Grid" grid containing rows:
      | Last Name |
      | Doe       |
      | Pitt      |
      | Williams  |
    When I reset "Role Users Grid" grid

    And I sort "Role Users Grid" by "Primary Email"
    Then I should see following "Role Users Grid" grid containing rows:
      | Primary Email      |
      | admin@example.com  |
      | brad@pitt.com      |
      | wendy@williams.com |
    When I reset "Role Users Grid" grid

    And I sort "Role Users Grid" by "Username"
    Then I should see following "Role Users Grid" grid containing rows:
      | Username |
      | admin    |
      | brad     |
      | wendy    |

  Scenario: Create user role
    Given I save and close form
    Then I should see validation errors:
      | Role | This value should not be blank. |
    And I fill form with:
      | Role        | Test role              |
      | Description | Hello it's description |
    And select following permissions:
      | Comment   | View:Division     | Create:Business Unit | Edit:User         | Delete:User         | Assign:User            |
      | Dashboard | View:Division     | Create:User          | Edit:User         | Delete:User         | Assign:User            |
      | Language  | View:Organization | Create:Organization  | Edit:Organization | Assign:Organization | Translate:Organization |
      | Note      | View:Division     | Create:Business Unit | Edit:User         | Delete:User         | Assign:User            |
    And I check "Access system information" entity permission
    When I save and close form
    Then I should see "Test role"
    And the role has following active permissions:
      | Comment   | View:Division     | Create:Business Unit | Edit:User         | Delete:User         | Assign:User            |
      | Dashboard | View:Division     | Create:User          | Edit:User         | Delete:User         | Assign:User            |
      | Language  | View:Organization | Create:Organization  | Edit:Organization | Assign:Organization | Translate:Organization |
      | Note      | View:Division     | Create:Business Unit | Edit:User         | Delete:User         | Assign:User            |
    And following capability permissions should be checked:
      | Access system information |

  Scenario: Edit user role
    Given I click "Edit"
    When I fill form with:
      | Role        | Edited test role              |
      | Description | Hello it's edited description |
    And select following permissions:
      | Grid view      | View:Business Unit | Create:Business Unit | Edit:Division | Delete:Division | Assign:User     |
      | Email Template | View:Global        | Create:Division      | Edit:Global   | Assign:Division | Assign:Division |
      | Note           | View:User          | Create:Business Unit | Edit:User     | Delete:User     | Assign:User     |
    And I check "Access system information" entity permission
    And I check "Access job queue" entity permission
    And I check "Access entity management" entity permission
    When I save and close form
    Then I should see "Edited test role"
    And the role has following active permissions:
      | Grid view      | View:Business Unit | Create:Business Unit | Edit:Division     | Delete:Division     | Assign:User            |
      | Email Template | View:Global        | Create:Division      | Edit:Global       | Assign:Division     | Assign:Division        |
      | Note           | View:User          | Create:Business Unit | Edit:User         | Delete:User         | Assign:User            |
      | Comment        | View:Division      | Create:Business Unit | Edit:User         | Delete:User         | Assign:User            |
      | Dashboard      | View:Division      | Create:User          | Edit:User         | Delete:User         | Assign:User            |
      | Language       | View:Organization  | Create:Organization  | Edit:Organization | Assign:Organization | Translate:Organization |
    And following capability permissions should be checked:
      | Access system information |
      | Access job queue          |
      | Access entity management  |

  Scenario: Delete user role
    Given I go to System/User Management/Roles
    Then I should see Edited test role in grid
    And I keep in mind number of records in list
    When I click Delete Edited test role in grid
    And I confirm deletion
    Then the number of records decreased by 1
    And I should not see "Edited test role"

  Scenario: Clone role and verify users grid filtering works
    Given I go to System/User Management/Roles
    When I click "Clone" on row "Administrator" in grid
    And I click "Users"
    And I filter First Name as contains "John" in "Role Users Grid"
    Then I should see following "Role Users Grid" grid containing rows:
      | First Name |
      | John       |

  Scenario: Check should see only the following actions
    Given I go to System/User Management/Roles
    And I accept alert
    And I should see only following actions for row #1 on grid:
      | Clone  |
      | View   |
      | Edit   |
      | Delete |
