Feature: Managing users roles
  In order to control user permissions
  As an Administrator
  I want to be able to manage user roles

  Scenario: User role create
    Given I login as administrator
    And go to System/User Management/Roles
    When I press "Create Role"
    And I fill form with:
      | Role        | Test role              |
      | Description | Hello it's description |
    And select following permissions:
      | Call        | View:Division      | Create:Business Unit | Edit:User | Delete:User | Assign:User |
      | Lead        | View:Division      | Create:User          | Edit:User | Delete:User | Assign:User |
      | Language    | View:Business Unit | Create:User          | Edit:User | Assign:User | Translate:User |
      | Task        | View:Division      | Create:Business Unit | Edit:User | Delete:User | Assign:User |
    And I check "Access dotmailer statistics" entity permission
    And I check "Manage Abandoned Cart Campaigns" entity permission
    Then I save and close form

  Scenario: Created user role view
    Given I should see "Test role"
    Then I should see following active permissions:
      | Call        | View:Division      | Create:Business Unit | Edit:User | Delete:User | Assign:User |
      | Lead        | View:Division      | Create:User          | Edit:User | Delete:User | Assign:User |
      | Language    | View:Business Unit | Create:User          | Edit:User | Assign:User | Translate:User |
      | Task        | View:Division      | Create:Business Unit | Edit:User | Delete:User | Assign:User |
    And I should see following capability permissions checked:
      | Access dotmailer statistics     |
      | Manage Abandoned Cart Campaigns |

  Scenario: Edit user role
    Given I press "Edit"
    When I fill form with:
      | Role        | Edited test role              |
      | Description | Hello it's edited description |
    And select following permissions:
      | Contact        | View:Business Unit | Create:Business Unit | Edit:Division | Delete:Division      | Assign:User     |
      | Email Campaign | View:Global        | Create:Division      | Edit:Global   | Assign:Division      | Assign:Division |
      | Task           | View:User          | Create:Business Unit | Edit:User     | Delete:User          | Assign:User     |
    And I check "Access dotmailer statistics" entity permission
    And I check "Manage Abandoned Cart Campaigns" entity permission
    And I check "Access job queue" entity permission
    And I check "Access entity management" entity permission
    When I save and close form
    Then I should see "Edited test role"
    And I should see following active permissions:
      | Contact        | View:Business Unit | Create:Business Unit | Edit:Division | Delete:Division      | Assign:User     |
      | Email Campaign | View:Global        | Create:Division      | Edit:Global   | Assign:Division      | Assign:Division |
      | Task           | View:User          | Create:Business Unit | Edit:User     | Delete:User          | Assign:User     |

      | Call           | View:Division      | Create:Business Unit | Edit:User     | Delete:User       | Assign:User    |
      | Lead           | View:Division      | Create:User          | Edit:User     | Delete:User       | Assign:User    |
      | Language       | View:Business Unit | Create:User          | Edit:User     | Assign:User       | Translate:User |
    And I should see following capability permissions checked:
      | Access dotmailer statistics     |
      | Manage Abandoned Cart Campaigns |
      | Access job queue                |
      | Access entity management        |
