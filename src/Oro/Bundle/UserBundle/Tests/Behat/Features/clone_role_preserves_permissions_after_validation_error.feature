@regression
@ticket-BAP-14133

Feature: Clone role preserves permissions after validation error
  In order to properly clone roles
  As an Administrator
  I want permissions to be preserved when form validation fails during role cloning

  Scenario: Clone Administrator and change permissions
    Given I login as administrator
    And go to System/User Management/Roles
    And I click Clone Administrator in grid
    And select following permissions:
      | Account | View:User | Create:User | Edit:User | Delete:User | Assign:User |
    And I uncheck "Access system information" entity permission
    And I uncheck "Login as Customer User" entity permission
    And select following permissions:
      | Alternative Checkout | View Workflow:User | Perform transitions:User |

  Scenario: Trigger validation error with duplicate name
    And I fill form with:
      | Role | Administrator |
    And I save and close form
    Then I should see validation errors:
      | Role | This value is already used. |

  Scenario: Fix name and save
    And I fill form with:
      | Role | Copy of Administrator |
    When I save and close form
    Then I should see "Role saved" flash message

  Scenario: Verify all permissions preserved
    And the role has following active permissions:
      | Account | View:User | Create:User | Edit:User | Delete:User | Assign:User |
    And following capability permissions should be unchecked:
      | Access system information |
      | Login as Customer User    |
    And the role has following active workflow permissions:
      | Alternative Checkout | View Workflow:User | Perform transitions:User |

  Scenario: Cleanup
    Given I go to System/User Management/Roles
    When I click Delete "Copy of Administrator" in grid
    And I confirm deletion
    Then I should not see "Copy of Administrator"
