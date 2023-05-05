@behat-test-env
@ticket-BB-17850
@feature-BAP-19790
Feature:  Digital asset management
  In order to have a possibility to manage digital assets
  As an Admin user
  I need to be able to create digital assets and use them in other entities

  Scenario: Check validation errors while create digital asset
    Given I login as administrator
    And I go to Marketing/ Digital Assets
    And I click "Create Digital Asset"
    When I save form
    Then I should see validation errors:
      | File | This value should not be blank. |
    And I fill "Digital Asset Form" with:
      | File  | sample.html             |
      | Title | Not supported mime type |
    When I save form
    Then I should see validation errors:
      | File | The MIME type of the file is invalid ("text/html"). Allowed MIME types are "image/svg+xml", "image/svg", "text/csv", "text/plain", "application/msword", "application/vnd.openxmlformats-officedocument.wordprocessingml.document", "application/vnd.ms-excel", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "application/vnd.ms-powerpoint", "application/vnd.openxmlformats-officedocument.presentationml.presentation", "application/pdf", "application/zip", "image/gif", "image/jpeg", "image/png", "image/webp". |

  Scenario: Create digital assets with jpg mime types
    Given I fill "Digital Asset Form" with:
      | File  | cat1.jpg        |
      | Title | JPG image asset |
    When I save form
    Then I should see "Digital Asset has been saved" flash message
    And I should see "cat1.jpg"
    And I should see picture "Digital Asset Preview" element
    And I go to Marketing/ Digital Assets
    And I click "Create Digital Asset"
    And I fill "Digital Asset Form" with:
      | File  | cat2.jpg              |
      | Title | Temporary image asset |
    When I save and close form
    Then I should see "Digital Asset has been saved" flash message
    And I should see following grid:
      | Title                 | File name | File size| Mime type  |
      | Temporary image asset | cat2.jpg  | 61.51 KB | image/jpeg |
      | JPG image asset       | cat1.jpg  | 76.77 KB | image/jpeg |

  Scenario: Check digital asset can be deleted from the grid
    Given I click delete "Temporary image asset" in grid
    When I click "Yes, Delete"
    Then I should see "Digital asset deleted" flash message
    And I should not see "Temporary image asset"

  Scenario: Create extend fields with DAM option enabled
    Given I go to System/ Entities/ Entity Management
    And I filter Name as is equal to "User"
    And I click view "User" in grid
    And I click "Create Field"
    And I fill form with:
      | Field Name   | image_asset  |
      | Storage Type | Table column |
      | Type         | Image        |
    And click "Continue"
    And I fill form with:
      | File Size (MB)        | 5000                    |
      | Thumbnail Width       | 190                     |
      | Thumbnail Height      | 120                     |
      | Allowed MIME types    | [image/jpeg, image/png] |
      | Use DAM               | Yes                     |
    When I save and create new form
    Then I should see "This value should be between 1 and 2,047."
    And I fill form with:
      | File Size (MB) | 5 |
    When I save and create new form
    Then I should see "Field saved" flash message
    And I fill form with:
      | Field Name   | file_asset   |
      | Storage Type | Table column |
      | Type         | File         |
    And click "Continue"
    And I fill form with:
      | File Size (MB)        | 5                 |
      | Allowed MIME types    | [application/pdf] |
      | Use DAM               | Yes               |
    When I save and close form
    Then I should see "Field saved" flash message
    And I click update schema

  Scenario: Attach existing image asset to User entity
    Given I click My User in user menu
    And I click "Entity Edit Button"
    And I click "Choose Image"
    And I should see picture "Digital Assets Grid First Row Image Picture" element
    When click on JPG image asset in grid
    Then I should see "cat1.jpg"
    When I save form
    Then I should see "cat1.jpg"
    And I download file "cat1.jpg"

  Scenario: Check digital asset can be removed from the entity
    Given I should not see "Choose Image"
    And I click "Digital Asset Remove Button"
    And I should see "Choose Image"
    When I save form
    Then I should not see "cat1.jpg"

  Scenario: Check appropriate mime type validation (depending on field config) applied for Digital Asset Dialog Form
    Given I click "Choose Image"
    And I fill "Digital Asset Dialog Form" with:
      | File  | example.pdf     |
      | Title | PNG image asset |
    When I click "Upload"
    Then I should see validation errors:
      | Picture | The MIME type of the file is invalid ("application/pdf"). Allowed MIME types are "image/jpeg", "image/png". |

  Scenario: Check "Clear" button works
    Given I fill "Digital Asset Dialog Form" with:
      | File    | example.pdf |
    And I click "Clear"
    When I click "Upload"
    Then I should see validation errors:
      | Picture | This value should not be blank. |
    And I close ui dialog

  Scenario: Check validation while saving not existing file (which was removed in other popup)
    Given I click "Choose Image"
    And click on cat1.jpg in grid
    And I click "Digital Asset Edit Button"
    And I click delete "cat1.jpg" in grid
    And I click "Yes, Delete"
    And I close ui dialog
    When I save form
    Then I should see "The chosen digital asset does not exist or has been deleted"

  Scenario: Check uploading PNG image type in dialog popup
    Given I click "Digital Asset Edit Button"
    And I fill "Digital Asset Dialog Form" with:
      | File  | 300x300.png     |
      | Title | PNG image asset |
    And I click "Upload"
    And I click on 300x300.png in grid
    When I save form
    Then I should see "300x300.png"
    And I download file "300x300.png"

  Scenario: Check there is no Images in File assets grid
    Given I click "Choose File"
    And I should not see "JPG image asset"
    And I should not see "PNG image asset"
    And I should see "There are no files"

  Scenario: Upload new PDF digital asset via file dialog popup
    Given I fill "Digital Asset Dialog Form" with:
      | File  | example.pdf    |
      | Title | PDF file asset |
    And I click "Upload"
    When click on PDF file in grid
    Then I should see "example.pdf"
    And I download file "example.pdf"
    And I save form

  Scenario: Check newly uploaded assets present on main grid
    Given I go to Marketing/ Digital Assets
    And I should see following grid:
      | Title            | File name   | Mime type       |
      | PDF file asset   | example.pdf | application/pdf |
      | PNG image asset  | 300x300.png | image/png       |

  Scenario: Check that used assets can not be removed
    Given I should not see following actions for PDF file asset in grid:
      | Delete |
    And I should not see following actions for PNG image asset in grid:
      | Delete |

  Scenario: Check updating existing digital asset updates child assets as well
    Given I click edit "PDF file asset" in grid
    And I fill "Digital Asset Form" with:
      | Title | UPDATED PDF file asset  |
      | File  | example2.pdf            |
    When I save and close form
    Then I should see "Digital Asset has been saved" flash message
    When I click My User in user menu
    Then I should see "example2.pdf"
    And I download file "example2.pdf"
