@ticket-BAP-11576
@ticket-BAP-21323

Feature: User Login Attempts
  In order to have ability to manage user logins
  As administrator
  I need to have ability see the list of login attempts

  Scenario: Try to login with wrong user
    Given I am on Login page
    And I fill "Login Form" with:
      | Username | wrong_user |
      | Password | Pa$$w0rd   |
    When I click "Log in"

  Scenario: Login users attempts
    Given I login as administrator
    And go to System/User Management/Login Attempts
    Then there are 2 records in grid
    And I should see following grid:
      | Success | Source  | Username   | User     |
      | Yes     | Default | admin      | John Doe |
      | No      | Default | wrong_user |          |

  Scenario: Check users attempts grid "Username" filter
    When I set filter "Username" as is equal to "wrong_user" and press Enter key
    Then I should see following grid:
      | Success | Source  | Username   | User     |
      | No      | Default | wrong_user |          |
    When I set filter "Username" as is equal to "admin" and press Enter key
    Then I should see following grid:
      | Success | Source  | Username   | User     |
      | Yes     | Default | admin      | John Doe |
    When I reset "Username" filter
    Then there are 2 records in grid

  Scenario: Check users attempts grid "Source" filter
    When I check "Impersonation" in Source filter
    Then there are 0 records in grid
    And I reset "Source" filter

  Scenario: Sort by Success field
    When I sort grid by "Success"
    Then I should see following grid:
      | Success | Source  | Username   | User     |
      | No      | Default | wrong_user |          |
      | Yes     | Default | admin      | John Doe |
    When I sort grid by "Success"
    Then I should see following grid:
      | Success | Source  | Username   | User     |
      | Yes     | Default | admin      | John Doe |
      | No      | Default | wrong_user |          |

  Scenario: Sort by Username field
    When I sort grid by "Username"
    Then I should see following grid:
      | Success | Source  | Username   | User     |
      | Yes     | Default | admin      | John Doe |
      | No      | Default | wrong_user |          |
    When I sort grid by "Username"
    Then I should see following grid:
      | Success | Source  | Username   | User     |
      | No      | Default | wrong_user |          |
      | Yes     | Default | admin      | John Doe |
    When I sort grid by "Username"
    Then I should see following grid:
      | Success | Source  | Username   | User     |
      | Yes     | Default | admin      | John Doe |
      | No      | Default | wrong_user |          |

  Scenario: Sort by Attempt at field
    When I sort grid by "Attempt at"
    Then I should see following grid:
      | Success | Source  | Username   | User     |
      | No      | Default | wrong_user |          |
      | Yes     | Default | admin      | John Doe |
    When I sort grid by "Attempt at"
    Then I should see following grid:
      | Success | Source  | Username   | User     |
      | Yes     | Default | admin      | John Doe |
      | No      | Default | wrong_user |          |

  Scenario: Sort by User field
    When I sort grid by "User"
    Then I should see following grid:
      | Success | Source  | Username   | User     |
      | Yes     | Default | admin      | John Doe |
      | No      | Default | wrong_user |          |
    When I sort grid by "User"
    Then I should see following grid:
      | Success | Source  | Username   | User     |
      | No      | Default | wrong_user |          |
      | Yes     | Default | admin      | John Doe |
