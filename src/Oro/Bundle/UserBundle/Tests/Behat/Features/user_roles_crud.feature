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

  Scenario: User role view
    Given I should see "Test role"
    Then I should see following active permissions:
      | Call        | View:Division      | Create:Business Unit | Edit:User | Delete:User | Assign:User |
      | Lead        | View:Division      | Create:User          | Edit:User | Delete:User | Assign:User |
      | Language    | View:Business Unit | Create:User          | Edit:User | Assign:User | Translate:User |
      | Task        | View:Division      | Create:Business Unit | Edit:User | Delete:User | Assign:User |
    And I should see following capability permissions checked:
      | Access dotmailer statistics     |
      | Manage Abandoned Cart Campaigns |
