@fixture-Users.yml
Feature: Transition button titles

  Scenario: Prepare Test Workflow
    Given I login as administrator
    Then I go to System/ Workflows

    And I press "Create Workflow"
    And I fill form with:
      | Name           | Workflow Button Titles |
      | Related Entity | User                   |

    And I press "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step Without Titles |
    And I press "Apply"
    And I press "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step Without Button Title |
    And I press "Apply"
    And I press "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step With Titles |
    And I press "Apply"
    And I press "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step With Titles and Attributes |
    And I press "Apply"
    And I press "Add step"
    And I fill "Workflow Step Edit Form" with:
      | Name | Step on Page With Titles and Attributes |
    And I press "Apply"

    And I press "Add transition"
    And I fill "Workflow Transition Edit Info Form" with:
      | Name            | Transit Without Titles                 |
      | From step       | (Start)                                |
      | To step         | Step Without Titles                    |
      | Warning message | Transit Without Titles Warning Message |
    And press "Apply"
    And press "Add transition"
    And fill "Workflow Transition Edit Info Form" with:
      | Name            | Transit Without Button Title                 |
      | From step       | Step Without Titles                          |
      | To step         | Step Without Button Title                    |
      | Warning message | Transit Without Button Title Warning Message |
      | Button Label    | Transit Without Button Title Label           |
    And press "Apply"
    And press "Add transition"
    And fill "Workflow Transition Edit Info Form" with:
      | Name            | Transit With Titles                 |
      | From step       | Step Without Button Title           |
      | To step         | Step With Titles                    |
      | Warning message | Transit With Titles Warning Message |
      | Button Label    | Transit With Titles Label           |
      | Button Title    | Transit With Titles Title           |
    And press "Apply"
    And press "Add transition"
    And fill "Workflow Transition Edit Info Form" with:
      | Name            | Transit With Titles and Attributes                 |
      | From step       | Step With Titles                                   |
      | To step         | Step With Titles and Attributes                    |
      | Warning message | Transit With Titles and Attributes Warning Message |
      | Button Label    | Transit With Titles and Attributes Label           |
      | Button Title    | Transit With Titles and Attributes Title           |
    And press "Attributes"
    And fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | First name |
    And press "Add"
    And press "Apply"
    And press "Add transition"
    And fill "Workflow Transition Edit Info Form" with:
      | Name             | Transit on Page With Titles and Attributes                 |
      | From step        | Step With Titles and Attributes                            |
      | To step          | Step on Page With Titles and Attributes                    |
      | View form        | Separate page                                              |
      | Destination Page | Original Page                                              |
      | Warning message  | Transit on Page With Titles and Attributes Warning Message |
      | Button Label     | Transit on Page With Titles and Attributes Label           |
      | Button Title     | Transit on Page With Titles and Attributes Title           |
    And press "Attributes"
    And fill "Workflow Transition Edit Attributes Form" with:
      | Entity field | First name |
    And press "Add"
    And press "Apply"

    And save and close form
    And click "Activate"
    And click "Activate"
    # for now, in UI no way to change datagrids
    And append grid "users-grid" for active workflow "Workflow Button Titles"
    And go to System/ Localization/ Translations
    And press "Update Cache"

  Scenario: Check Titles on Datagrid
    Given I go to System/ User Management/ Users

    When I click "Transit Without Titles" on row "user1@example.com" in grid
    Then I should see "UiWindow" with elements:
      | Title        | Transit Without Title                 |
      | Content      | Transit Without Title Warning Message |
      | okButton     | Yes                                   |
      | cancelButton | Cancel                                |
    And click "Yes"
    Then I should see following grid:
      | Step | Workflow Button titles: Step Without Titles |

    When I click "Transit Without Button Title Label" on row "user1@example.com" in grid
    Then I should see "UiWindow" with elements:
      | Title        | Transit Without Button Title Label           |
      | Content      | Transit Without Button Title Warning Message |
      | okButton     | Yes                                          |
      | cancelButton | Cancel                                       |
    And click "Yes"
    Then I should see following grid:
      | Step | Workflow Button titles: Step Without Button Title |

    When I click "Transit With Titles Label" on row "user1@example.com" in grid
    Then I should see "UiWindow" with elements:
      | Title        | Transit With Titles Label           |
      | Content      | Transit With Titles Warning Message |
      | okButton     | Yes                                 |
      | cancelButton | Cancel                              |
    And click "Yes"
    Then I should see following grid:
      | Step | Workflow Button titles: Step With Button Titles |

    When I click "Transit With Titles and Attributes Label" on row "user1@example.com" in grid
    Then I should see "UiDialog" with elements:
      | Title        | Transit With Titles and Attributes Label |
      | okButton     | Submit                                   |
      | cancelButton | Cancel                                   |
    And click "Submit"
    Then I should see following grid:
      | Step | Workflow Button titles: Step With Titles and Attributes |

    When I click "Transit on Page With Titles and Attributes Label" on row "user1@example.com" in grid
    Then I should see that "Workflow Page Title" contains "Transit on Page With Titles and Attributes Label"
    And I should see "Transit on Page With Titles and Attributes Warning Message"
    And click "Submit"
    Then I should see following grid:
      | Step | Workflow Button titles: Step on Page With Titles and Attributes |

  Scenario: Check Titles on View Page
    Given I go to System/ User Management/ Users
    And click View user2@example.com in grid

    Then I should see "Transit Without Titles" button with attributes:
      | title | Transit Without Titles |
    When I click "Transit Without Titles"
    Then I should see "UiWindow" with elements:
      | Title        | Transit Without Title                 |
      | Content      | Transit Without Title Warning Message |
      | okButton     | OK                                    |
      | cancelButton | Cancel                                |
    And click "OK"

    Then I should see "Transit Without Button Title Label" button with attributes:
      | title | Transit Without Button Title Label |
    When I click "Transit Without Button Title Label"
    Then I should see "UiWindow" with elements:
      | Title        | Transit Without Button Title Label           |
      | Content      | Transit Without Button Title Warning Message |
      | okButton     | OK                                           |
      | cancelButton | Cancel                                       |
    And click "OK"

    Then I should see "Transit With Titles Label" button with attributes:
      | title | Transit With Titles Title |
    When I click "Transit With Titles Label"
    Then I should see "UiWindow" with elements:
      | Title        | Transit With Titles Label           |
      | Content      | Transit With Titles Warning Message |
      | okButton     | OK                                  |
      | cancelButton | Cancel                              |
    And click "OK"

    Then I should see "Transit With Titles and Attributes Label" button with attributes:
      | title | Transit With Titles and Attributes Title |
    When I click "Transit With Titles and Attributes Label"
    Then I should see "UiDialog" with elements:
      | Title        | Transit With Titles and Attributes Label |
      | okButton     | Submit                                   |
      | cancelButton | Cancel                                   |
    And click "Submit"

    Then I should see "Transit on Page With Titles and Attributes Label" button with attributes:
      | title | Transit on Page With Titles and Attributes Title |
    When I click "Transit on Page With Titles and Attributes Label"
    Then I should see that "Workflow Page Title" contains "Transit on Page With Titles and Attributes Label"
    And I should see "Transit on Page With Titles and Attributes Warning Message"
    And click "Submit"
