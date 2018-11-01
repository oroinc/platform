@ticket-BB-14685
@fixture-OroUserBundle:user.yml

Feature: User Case Insensitive Email Addresses
  In order to avoid possible user mistakes
  As an administrator
  I want to disable admin user registration with emails if the same email with a different capitalization already belongs to some backoffice user
  As a user
  I must be able to login with email in another case if "Case Insensitive Email Addresses" is enabled
  I must not be able to login with email in another case if "Case Insensitive Email Addresses" is disabled

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Check unsuccessful login with email in another case
    Given I proceed as the User
    And I am on Login page
    And I fill "Login Form" with:
      | Username | CHARLIE@example.com |
      | Password | charlie             |
    When I click "Log in"
    Then I should see "Your login was unsuccessful. Please check your e-mail address and password before trying again. If you have forgotten your password, follow \"Forgot your password?\" link."

  Scenario: Enable "Case Insensitive Email Addresses" configuration option
    Given I proceed as the Admin
    And I login as administrator
    And go to System/ Configuration
    And follow "System Configuration/General Setup/User Settings" on configuration sidebar
    And I check "Case-Insensitive Email Addresses"
    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Check successful login with email in another case when "Case Insensitive Email Addresses" is enabled
    Given I proceed as the User
    And I am on Login page
    And I fill "Login Form" with:
      | Username | CHARLIE@example.com |
      | Password | charlie             |
    When I click "Log in"
    Then I should be on Admin Dashboard page

  Scenario: Check successful login with original email when "Case Insensitive Email Addresses" is enabled
    Given I am logged out
    And I am on Login page
    And I fill form with:
      | Username | Charlie@example.com |
      | Password | charlie             |
    When I click "Log in"
    Then I should be on Admin Dashboard page

  Scenario: Check registration is not allowed when same email in lowercase exists when "Case Insensitive Email Addresses" is enabled
    Given I proceed as the Admin
    And go to System/User Management/Users
    And click "Create User"
    When I fill "User Form" with:
      | Username          | John                |
      | Password          | Pa$$w0rd            |
      | Re-Enter Password | Pa$$w0rd            |
      | First Name        | John                |
      | Last Name         | Connor              |
      | Primary Email     | CHARLIE@example.com |
      | Roles             | Administrator       |
      | Enabled           | Enabled             |
    And I save and close form
    Then I should see validation errors:
      | Primary Email | This email is already registered by another user. Please provide unique email address. |

  Scenario: Check that you cant enable "Case Insensitive Email Addresses" options while there are users with same lowercase emails exist
    Given I proceed as the Admin
    When I go to System/Configuration
    And follow "System Configuration/General Setup/User Settings" on configuration sidebar
    And I uncheck "Case-Insensitive Email Addresses"
    And I save form
    Then I should see "Configuration saved" flash message

    When I go to System/User Management/Users
    And I click "Create User"
    When I fill "User Form" with:
      | Username          | John                |
      | Password          | Pa$$w0rd            |
      | Re-Enter Password | Pa$$w0rd            |
      | First Name        | John                |
      | Last Name         | Connor              |
      | Primary Email     | CHARLIE@example.com |
      | Roles             | Administrator       |
      | Enabled           | Enabled             |
    And I save and close form
    Then I should see "User saved" flash message

    When I go to System/Configuration
    And follow "System Configuration/General Setup/User Settings" on configuration sidebar
    And I check "Case-Insensitive Email Addresses"
    And I save form
    Then I should see "there are existing users who have identical lowercase emails"
    When I click "Click here"
    Then I should be on User Index page
    And records in current page grid should be 2
    And I should see following records in grid:
      | Charlie    |
      | John       |
