@regression
@ticket-BB-17538

Feature: Files are removed from filesystem after removing them from product
  Scenario: Saving and deleting product image files with default settings
    Given I login as administrator
    And I go to Products / Products
    And I click "Create Product"
    And I click "Continue"
    And I fill "Create Product Form" with:
      | SKU    | PSKU1     |
      | Name   | Product 1 |
      | Status | Enabled   |
    And I set Images with:
      | File     | Main  | Listing | Additional |
      | cat1.jpg | 1     | 1       | 1          |
    When I save form
    Then I should see "Product has been saved" flash message
    And I expect image files created and remember paths:
      | cat1.jpg |
    When I remove "cat1" from File
    And I save and close form
    Then I should see "Product has been saved" flash message
    And I expect paths don't exist for images:
      | cat1.jpg |

  Scenario: Saving and deleting product image files with human readable image URLs
    Given I go to System / Configuration
    And I follow "Commerce/Product/Product Images" on configuration sidebar
    And uncheck "Use default" for "Enable Original File Names" field
    And I check "Enable Original File Names"
    When I save form
    Then I should see "Configuration saved" flash message
    And I go to Products / Products
    And I click edit "PSKU1" in grid
    And I set Images with:
      | File     | Main  | Listing | Additional |
      | cat1.jpg | 1     | 1       | 1          |
    When I save form
    Then I should see "Product has been saved" flash message
    And I expect image files created and remember paths:
      | cat1.jpg |
    When I remove "cat1" from File
    And I save and close form
    Then I should see "Product has been saved" flash message
    And I expect paths don't exist for images:
      | cat1.jpg |
