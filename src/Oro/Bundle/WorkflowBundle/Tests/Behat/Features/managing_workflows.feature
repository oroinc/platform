Feature: Managing workflows
  In order to check workflows crud
  As an Administrator
  I want to be able to manage workflow entity

  Scenario: Workflow creation
    Given I login as administrator
    Then I go to System/ Workflows
    And I click "Create Workflow"
    And I fill form with:
      | Name            | Test workflow |
      | Related Entity  | User          |
    Then I click "Add step"
    And I fill form with:
      | label           | Step1  |
    And I click "Apply"
    Then I click "Add transition"
    And I fill form with:
      | label           | Trans1  |
      | step_from       | (Start) |
      | step_to         | Step1   |
      | button_label    | Label1  |
      | button_title    | Title1  |
    And I click "Apply"
    Then I click "Add step"
    And I fill form with:
      | label           | Step2  |
      | order           | 1      |
      | is_final        | true   |
    And I click "Apply"
    Then I click "Add transition"
    And I fill form with:
      | label           | Trans2  |
      | step_from       | Step1   |
      | step_to         | Step2   |
      | button_label    | Label2  |
      | button_title    | Title2  |
    And I click "Apply"
    When I save and close form
    And I go to System/ Workflows
    Then I should see Test workflow in grid with following data:
      | Related Entity  | User |
      | Active          | No   |
      | System          | No   |
      | Priority        | 0    |

  Scenario: Workflow activation from grid
    Given I sort grid by Related Entity
    Then I should see following actions for Test Workflow in grid:
      | Edit |
    And I click Activate Test workflow in grid
    And I click "Activate"
    Then I should see "Workflow activated" flash message
    And I should not see following actions for Test Workflow in grid:
      | Edit |
    And I should see Test workflow in grid with following data:
      | Related Entity  | User |
      | Active          | Yes  |
      | System          | No   |
      | Priority        | 0    |

  Scenario: Workflow deactivation from grid
    Given I sort grid by Related Entity
    When I click Deactivate Test workflow in grid
    And I click "Yes, Deactivate"
    Then I should see following actions for Test Workflow in grid:
      | Edit |
    And I should see Test workflow in grid with following data:
      | Related Entity  | User |
      | Active          | No   |
      | System          | No   |
      | Priority        | 0    |

  Scenario: Workflow activation from entity view
    Given I click View Test workflow in grid
    Then I should see an "Entity Edit Button" element
    And I click "Activate"
    And I click "Activate"
    Then I should see "Workflow activated" flash message
    And I should not see an "Entity Edit Button" element

  Scenario: Workflow deactivation from entity view
    Given I click "Deactivate"
    And I click "Yes, Deactivate"
    Then I should see "Workflow deactivated" flash message
    And I should see an "Entity Edit Button" element

  Scenario: Workflow edit
    Given I go to System/ Workflows
    And I click Edit Test workflow in grid
    When I click "Trans1"
    Then "Workflow Transition Edit Info Form" must contains values:
      | label           | Trans1  |
      | button_label    | Label1  |
      | button_title    | Title1  |
    And I click "Cancel"
    When I fill form with:
      | Name            | Glorious workflow  |
      | Related Entity  | Business Unit      |
    And I save and close form
    Then I should see "Could not save workflow. Please add at least one step and one transition." flash message
    Then I click "Add step"
    And I fill form with:
      | label           | Step1  |
      | order           | 1      |
      | is_final        | true   |
    And I click "Apply"
    Then I click "Add transition"
    And I fill form with:
      | label           | Trans1  |
      | step_from       | (Start) |
      | step_to         | Step1   |
    And I click "Apply"
    When I save and close form
    And I go to System/ Workflows
    Then I should see Glorious workflow in grid with following data:
      | Related Entity  | Business Unit      |

  Scenario: Workflow clone
    Given I sort grid by Related Entity
    And I click Clone Glorious workflow in grid
    When I save and close form
    Then I should see "Translation cache update is required. Click here to update" flash message
    And I should see "Workflow saved." flash message
    When I go to System/ Workflows
    Then I should see Copy of Glorious workflow in grid with following data:
      | Related Entity  | Business Unit              |
      | Active          | No                         |
      | System          | No                         |
      | Priority        | 0                          |

  Scenario: Deleting workflow
    Given I sort grid by Related Entity
    And I click Delete Copy of Glorious workflow in grid
    When I confirm deletion
    Then I should see "Item deleted" flash message
    And there is no "Copy of Glorious workflow" in grid
    When I click view Glorious workflow in grid
    And I click "Delete Workflow"
    And I confirm deletion
    Then there is no "Glorious workflow" in grid
