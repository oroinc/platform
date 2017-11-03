@regression
@fixture-OroUserBundle:manager.yml
Feature: Update user profile acl
  In order to control for users ability to update their own profile
  As administrator
  I need to have ability to manage this permission independently of user entity permissions

  Scenario: User Profile capability is on and User Edit permission is None for Sales Manager Role by default
    Given sessions active:
      | Admin   | first_session  |
      | Manager | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I open "Sales Manager" role view page
    Then the role has following active permissions:
      | User | View:Global | Edit:None |
    And following capability permissions should be checked:
      | Update User Profile |

  Scenario: Edit Button is shown when Update User Profile capability is on and User Edit permission is None
    Given I proceed as the Manager
    And I login as "ethan" user
    And I open User Profile Update page

  Scenario: Manager can update profile when Update User Profile capability is on and User Edit permission is None
    Given I proceed as the Manager
    And I open User Profile Update page
    And I fill form with:
      | First Name | Bobby   |
      | Last Name  | Fischer |
    And I save form
    Then I should see "User saved" flash message
    When I reload the page
    Then I should see "Bobby Fischer" in the "User Menu" element

  Scenario: Disable Update User Profile capability and set User Edit permission to None for Sales Manager Role
    Given I proceed as the Admin
    And I should be on Role View page
    When I click "Edit"
    And I uncheck "Update User Profile" entity permission
    And I save and close form
    Then I should see "Role Saved" flash message
    And the role has following active permissions:
      | User | View:Global | Edit:None |
    And following capability permissions should be unchecked:
      | Update User Profile |

  Scenario: Manager can't update profile when Update User Profile capability is off and User Edit permission is None
    Given I proceed as the Manager
    And I should be on User Profile Update page
    And I fill form with:
      | First Name | Boris   |
      | Last Name  | Spassky |
    And I save form
    Then I should see "You do not have permission to perform this action" error message

  Scenario: No Edit Button is shown when Update User Profile capability is off and User Edit permission is None
    When I click My User in user menu
    Then I should not see an "Edit Button" element

  Scenario: Turn on Update User Profile capability and set User Edit permission to Global for Sales Manager Role
    Given I proceed as the Admin
    And I should be on Role View page
    When I click "Edit"
    And I check "Update User Profile" entity permission
    And select following permissions:
      | User | View:Global | Edit:Global |
    And I save and close form
    Then I should see "Role Saved" flash message
    And the role has following active permissions:
      | User | View:Global | Edit:Global |
    And following capability permissions should be checked:
      | Update User Profile |

  Scenario: Manager can update profile when Update User Profile capability is on and User Edit permission is Global
    Given I proceed as the Manager
    And I should be on User Profile View page
    And I reload the page
    And I open User Profile Update page
    And I fill form with:
      | First Name | Anatoliy |
      | Last Name  | Karpov   |
    And I save form
    Then I should see "User saved" flash message
    When I reload the page
    Then I should see "Anatoliy Karpov" in the "User Menu" element

  Scenario: Turn off Update User Profile capability and set User Edit permission to Global for Sales Manager Role
    Given I proceed as the Admin
    And I should be on Role View page
    When I click "Edit"
    And I uncheck "Update User Profile" entity permission
    And select following permissions:
      | User | View:Global | Edit:Global |
    And I save and close form
    Then I should see "Role Saved" flash message
    And the role has following active permissions:
      | User | View:Global | Edit:Global |
    And following capability permissions should be unchecked:
      | Update User Profile |

  Scenario: Manager can't update profile when Update User Profile capability is off and User Edit permission is Global
    Given I proceed as the Manager
    And I should be on User Profile Update page
    And I fill form with:
      | First Name | Boris   |
      | Last Name  | Spassky |
    And I save form
    Then I should see "You do not have permission to perform this action" error message

  Scenario: No Edit Button is shown when Update User Profile capability is off and User Edit permission is Global
    Given I proceed as the Manager
    When I click My User in user menu
    Then I should not see an "Edit Button" element
