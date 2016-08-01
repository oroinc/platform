Feature: User Notes
  In order to have ability to manage notes
  As OroCRM sales rep
  I need to have ability create, reade update and delete entity notes

Background:
  Given I login as "admin" user with "admin" password

Scenario: Add note to user entity
  Given I go to System/Entities/Entity Management
  And filter Name as is equal to "User"
  And click Edit User in grid
  And select "Yes" from "Enable notes"
  When I save and close form
  And click update schema
  Then I should see "Schema updated" flash message

Scenario: Add note
  Given the following user:
    | firstName | lastName | email             |
    | Charlie   | Sheen    | charlie@sheen.com |
  And I go to System/User Management/Users
  And click view Charlie in grid
  And follow "More actions"
  And press "Add note"
  And fill "Note" form with:
    | Message    | Charlie works hard  |
    | Attachment | note-attachment.jpg |
  When press "Add"
  Then I should see "Note saved" flash message
  And should see "Charlie works hard" note in activity list

Scenario: View note
  Given I go to System/User Management/Users
  And click view Charlie in grid
  When I collapse "Charlie works hard" in activity list
  Then I should see note-attachment.jpg text in activity

Scenario: Edit note in view page
  Given I go to System/User Management/Users
  And click view Charlie in grid
  And I click "Update note" on "Charlie works hard" in activity list
  And fill "Note" form with:
    | Message    | Very good actor      |
    | Attachment | note-attachment2.jpg |
  When I press "Save"
  Then I should see "Very good actor"
  When I collapse "Very good actor" in activity list
  And I should see note-attachment2.jpg text in activity

Scenario: Delete note
  Given I go to System/User Management/Users
  And click view Charlie in grid
  When I click "Delete note" on "Very good actor" in activity list
  And press "Yes, Delete"
  Then there is no records in activity list
