@ticket-BB-17850
@ticket-BB-18073
@ticket-BAP-21510
Feature: Digital asset removal restrictions
  In order to have a possibility to manage digital assets
  As an Administrator
  I need not to be able to remove digital assets until them are used

  Scenario: Create extend fields with DAM option enabled
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And I filter Name as is equal to "User"
    And I click view "User" in grid
    And I click "Create Field"
    And I fill form with:
      | Field Name   | image_asset  |
      | Storage Type | Table column |
      | Type         | Image        |
    And click "Continue"
    And I fill form with:
      | File Size (MB)        | 5                       |
      | Thumbnail Width       | 190                     |
      | Thumbnail Height      | 120                     |
      | Allowed MIME types    | [image/jpeg, image/png] |
      | Use DAM               | Yes                     |
    When I save and close form
    Then I should see "Field saved" flash message
    And I click update schema

  Scenario: Create digital asset
    Given I go to Marketing/ Digital Assets
    And I click "Create Digital Asset"
    And I fill "Digital Asset Form" with:
      | File  | cat1.jpg        |
      | Title | JPG image asset |
    When I save and close form
    Then I should see "Digital Asset has been saved" flash message
    And I should see following grid:
      | Title           | File name | File size | Mime type  |
      | JPG image asset | cat1.jpg  | 76.77 KB  | image/jpeg |
    And I should see following actions for JPG image asset in grid:
      | Delete |

  Scenario: Attach existing image asset to User entity
    Given I go to System/User Management/Users
    And I click "Create User"
    When fill "Create User Form" with:
      | Username          | user1Name      |
      | Password          | user1Name      |
      | Re-Enter Password | user1Name      |
      | First Name        | First Name     |
      | Last Name         | Last Name      |
      | Primary Email     | email@test.com |
      | Roles             | Administrator  |
      | Enabled           | Enabled        |
    And I click "Choose Image"
    And click on JPG image asset in grid
    And I save and close form
    Then I should see "User saved" flash message

  Scenario: Check digital asset actions
    When I go to Marketing/Digital Assets
    Then I should see following grid:
      | Title           | File name | File size | Mime type  |
      | JPG image asset | cat1.jpg  | 76.77 KB  | image/jpeg |
    And I should not see following actions for JPG image asset in grid:
      | Delete |

  Scenario: Remove User entity
    Given I go to System/User Management/Users
    And I should see "user1Name"
    When I click Delete user1Name in grid
    And I confirm deletion
    Then I should not see "user1Name"

  Scenario: Check digital asset actions
    When I go to Marketing/ Digital Assets
    Then I should see following grid:
      | Title           | File name | File size | Mime type  |
      | JPG image asset | cat1.jpg  | 76.77 KB  | image/jpeg |
    And I should see following actions for JPG image asset in grid:
      | Delete |

  Scenario: Update digital asset
    When I click "edit" on row "JPG image asset" in grid
    And I fill "Digital Asset Form" with:
      | File  | cat2.jpg                |
      | Title | JPG image asset updated |
    When I save and close form
    Then I should see "Digital Asset has been saved" flash message
    And I should see following grid:
      | Title                   | File name | Mime type  |
      | JPG image asset updated | cat2.jpg  | image/jpeg |
