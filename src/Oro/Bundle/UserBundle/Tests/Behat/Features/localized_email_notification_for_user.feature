@regression
@ticket-BAP-17336
@fixture-OroUserBundle:UserLocalizations.yml

Feature: Localized email notification for user
  In order to receive emails
  As a user
  I need to receive emails in predefined language

  Scenario: Prepare configuration with different languages on each level
    Given I login as administrator
    When I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English, German Localization, French Localization] |
      | Default Localization  | French Localization                                 |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I go to System / User Management / Organizations
    And click Configuration "Oro" in grid
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use System" for "Default Localization" field
    And I fill form with:
      | Default Localization | German Localization |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I click My Configuration in user menu
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use Organization" for "Default Localization" field
    And I fill form with:
      | Default Localization | English |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: An Email Invitation should be sent to a new user in a language of his config was successfully created
    Given I go to System / Emails / Templates
    When I filter Template Name as is equal to "invite_user"
    And I click "edit" on first row in grid
    And fill "Email Template Form" with:
      | Subject | English Invite Subject |
      | Content | English Invite Body    |
    And I click "French"
    And fill "Email Template Form" with:
      | Subject | French Invite Subject |
      | Content | French Invite Body    |
    And I click "German"
    And fill "Email Template Form" with:
      | Subject | German Invite Subject |
      | Content | German Invite Body    |
    And I submit form
    Then I should see "Template saved" flash message
    When I go to System / User Management / Users
    And click "Create User"
    And fill form with:
      | Username          | test          |
      | First Name        | first         |
      | Last Name         | last          |
      | Primary Email     | test@test.com |
      | Roles             | Administrator |
      | Enabled           | Enabled       |
      | Generate Password | true          |
    And I submit form
    Then I should see "User saved" flash message
    And Email should contains the following:
      | To      | test@test.com         |
      | Subject | German Invite Subject |
      | Body    | German Invite Body    |

  Scenario: A user should get an email about changed password in a language of its configuration
    Given I go to System / Emails / Templates
    When I filter Template Name as is equal to "user_change_password"
    And I click "edit" on first row in grid
    And fill "Email Template Form" with:
      | Subject | English Change Password Subject |
      | Content | English Change Password Body    |
    And I click "French"
    And fill "Email Template Form" with:
      | Subject | French Change Password Subject |
      | Content | French Change Password Body    |
    And I click "German"
    And fill "Email Template Form" with:
      | Subject | German Change Password Subject |
      | Content | German Change Password Body    |
    And I submit form
    Then I should see "Template saved" flash message
    When I go to System / User Management / Users
    And I click view "test@test.com" in grid
    And follow "More actions"
    And follow "Change password"
    And I click "Suggest password"
    And I click "Save"
    Then I should see "Reset password email has been sent to user" flash message
    And Email should contains the following:
      | To      | test@test.com                  |
      | Subject | German Change Password Subject |
      | Body    | German Change Password Body    |

  Scenario: A user should get an email about password's reset in a language of its configuration
    Given I go to System / Emails / Templates
    When I filter Template Name as is equal to "force_reset_password"
    And I click "edit" on first row in grid
    And fill "Email Template Form" with:
      | Subject | English Reset Password Subject |
      | Content | English Reset Password Body    |
    And I click "French"
    And fill "Email Template Form" with:
      | Subject | French Reset Password Subject |
      | Content | French Reset Password Body    |
    And I click "German"
    And fill "Email Template Form" with:
      | Subject | German Reset Password Subject |
      | Content | German Reset Password Body    |
    And I submit form
    Then I should see "Template saved" flash message
    When I go to System / User Management / Users
    And I click view "test@test.com" in grid
    And follow "More actions"
    And follow "Reset password"
    And I confirm reset password
    Then I should see "Password reset request has been sent to test@test.com." flash message
    And Email should contains the following:
      | To      | test@test.com                 |
      | Subject | German Reset Password Subject |
      | Body    | German Reset Password Body    |
