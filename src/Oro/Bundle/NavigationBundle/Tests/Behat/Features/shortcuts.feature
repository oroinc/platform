Feature: Shortcuts feature
  In order to decrease time for performing some commonly used tasks
  As a user
  I need to shortcuts functionality

  Scenario: Full list of shortcuts
    Given I login as "admin" user with "admin" password
    And I follow "Shortcuts"
    And I follow "See full list"
    And I should be on "/shortcutactionslist"

  Scenario: Choose shortcut from search
    Given I follow "Shortcuts"
    When I type "Create" in "Enter shortcut action"
    And click "Create new user" in shortcuts search results
    Then I should be on "/user/create"

  Scenario: Compose email from shortcut
    Given I follow "Shortcuts"
    When I type "Compose" in "Enter shortcut action"
    And click "Compose email" in shortcuts search results
    Then I should see an "EmailForm" element
