Feature: User user comment
  In order to have ability comment user
  As OroCRM sales rep
  I need to have comment user functionality

  Scenario: Add comments to user entity
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click Edit User in grid
    And select "Yes" from "Enable Comments"
    When I save and close form
    And click update schema
    Then I should see "Schema updated" flash message

  Scenario: Add comment
    Given the following users:
      | firstName | lastName | email              | username | organization  | organizations   | owner          | businessUnits    |
      | Charlie   | Sheen    | charlie@sheen.com  | charlie  | @organization | [@organization] | @business_unit | [@business_unit] |
    And I go to System/User Management/Users
    And click view Charlie in grid
    And press "Add Comment"
    When I fill "Comment" form with:
      | Message    | Amazing cat |
      | Attachment | cat.jpg     |
    And press "Add"
    Then I should see "Amazing cat"
    When I click on "cat.jpg" attachment thumbnail
    Then I should see large image
    And should not see "Download(9.40 KB)"
    When follow "cat.jpg"
    Then I should see "Download(9.40 KB)"
