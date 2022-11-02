@ticket-BAP-21510

Feature: Segment crud and export
  In order to simplify the process of segment creation
  As an administrator
  I need to be able to create, edit and delete a segment, also export it

  Scenario Outline: Create Dynamic and Manual segment
    Given I login as administrator
    When I go to Reports & Segments/ Manage Segments
    And I click "Create Segment"
    And I fill "Segment Form" with:
      | Name         | <segment_name> |
      | Entity       | User           |
      | Segment Type | <segment_type> |
    And I add the following columns:
      | Username |
    And I save form
    Then I should see "Segment saved" flash message

    Examples:
      | segment_name     | segment_type |
      | Username Dynamic | Dynamic      |
      | Username Manual  | Manual       |

  Scenario: View segments
    When I go to Reports & Segments/ Manage Segments
    Then there are 4 records in grid
    And I should see following grid containing rows:
      | Name             | Entity | Type    |
      | Username Dynamic | User   | Dynamic |
      | Username Manual  | User   | Manual  |

  Scenario: Create new User for segment testing
    When I go to System/User Management/Users
    And click "Create User"
    Then I fill "User Form" with:
      | Username          | userName       |
      | Password          | Pa$$w0rd       |
      | Re-Enter Password | Pa$$w0rd       |
      | First Name        | First Name     |
      | Last Name         | Last Name      |
      | Primary Email     | email@test.com |
      | Roles             | Administrator  |
      | Enabled           | Enabled        |
    And I save and close form
    And I should see "User saved" flash message

  Scenario: View Dynamic segment after adding new User
    When I go to Reports & Segments/ Manage Segments
    And I click "View" on row "Username Dynamic" in grid
    Then I should see following grid:
      | Username |
      | admin    |
      | userName |

  Scenario: Export Grid Dynamic segment
    When I filter Username as is equal to "admin"
    And I should see "Export Grid"
    And I click "Export Grid"
    And I click "CSV"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Grid export performed successfully. Download" text
    And exported file contains at least the following columns:
      | Username |
      | admin    |

  Scenario: View Manual segment after adding new User
    When I go to Reports & Segments/ Manage Segments
    And I click "View" on row "Username Manual" in grid
    Then I should see following grid:
      | Username |
      | admin    |
    When I click "Refresh segment"
    And I click "Yes" in confirmation dialogue
    Then I should see following grid:
      | Username |
      | admin    |
      | userName |

  Scenario: Export Grid Manual segment
    When I filter Username as is equal to "admin"
    And I should see "Export Grid"
    And I click "Export Grid"
    And I click "CSV"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Grid export performed successfully. Download" text
    And exported file contains at least the following columns:
      | Username |
      | admin    |

  Scenario Outline: Update segment
    When I go to Reports & Segments/ Manage Segments
    And I click "Edit" on row "<segment_name>" in grid
    And I fill "Segment Form" with:
      | Name | <segment_name> update |
    And I save form
    Then I should see "Segment saved" flash message

    Examples:
      | segment_name     |
      | Username Dynamic |
      | Username Manual  |

  Scenario Outline: Delete segment
    When I go to Reports & Segments/ Manage Segments
    And I click delete "<segment_name>" in grid
    And I confirm deletion
    Then I should see "Item deleted" flash message

    Examples:
      | segment_name            |
      | Username Dynamic update |
      | Username Manual update  |
