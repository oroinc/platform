Feature: User user comment
  In order to have ability comment user
  As OroCRM sales rep
  I need to have comment user functionality

  Scenario: Add comments to user entity
    Given the following users:
      | firstName | lastName | email              | username |
      | Charlie   | Sheen    | charlie@sheen.com  | charlie  |
    And I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click Edit User in grid
    And select "Yes" from "Enable Comments"
    When I save and close form
    And click update schema
    Then I should see "Schema updated" flash message

  Scenario: Add comment
    Given I go to System/User Management/Users
    And click view Charlie in grid
    And press "Add Comment"
    When I fill "Comment Form" with:
      | Message    | Amazing cat |
      | Attachment | cat.jpg     |
    And press "Add"
    Then I should see "Amazing cat"
    When I click on "cat.jpg" attachment thumbnail
    Then I should see large image
    And I close large image preview
    And download link for "cat.jpg" attachment should work
