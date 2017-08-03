@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
Feature: Improve manage grid swipe actions
  Scenario: Checked drag and drop option in "Manage grid" popup on mobile view
    Given I login as AmandaRCole@example.org the "Buyer" at "640_session" session
    And I set window size to 640x1100
    And click "Account Mobile Button"
    And click "Users"
    Then I should see following grid with exact columns order:
      | First Name | Last Name | Email Address           | Enabled | Confirmed |
      | Amanda     | Cole      | AmandaRCole@example.org | Yes     | Yes       |
    When click "Grid Settings"
    Then I should see an "Manage Grid Fullscreen Popup" element
    And click "Deselect All Button"
    And click "Close Fullscreen Popup"
    # Last enabled column remains selected when deselecting all columns in grid
    Then I should see following grid with exact columns order:
      | Confirmed |
      | Yes       |
    When click "Grid Settings"
    Then I should see an "Manage Grid Fullscreen Popup" element
    And click "Reset Grid"
    And click "Close Fullscreen Popup"
    Then I should see following grid with exact columns order:
      | First Name | Last Name | Email Address           | Enabled | Confirmed |
      | Amanda     | Cole      | AmandaRCole@example.org | Yes     | Yes       |
    When click "Grid Settings"
    Then I should see an "Manage Grid Fullscreen Popup" element
    When I drag and drop "Email Address Handle" before "First Name Handle"
    # Unchecking custom checkbox
    And click "Last Name In Grid Management"
    And click "Close Fullscreen Popup"
    Then I should see following grid with exact columns order:
      | Email Address           | First Name | Enabled | Confirmed |
      | AmandaRCole@example.org | Amanda     | Yes     | Yes       |
    When click "Grid Settings"
    Then I should see an "Manage Grid Fullscreen Popup" element
    And click "Select All Button"
    And click "Close Fullscreen Popup"
    Then I should see following grid with exact columns order:
      | Email Address           | First Name | Last Name | Enabled | Confirmed |
      | AmandaRCole@example.org | Amanda     | Cole      | Yes     | Yes       |
