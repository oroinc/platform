@regression
@ticket-BAP-19781

Feature: Image processor settings admin configuration
  In order to be able to adjust the image quality
  As an administrator
  I check the form of image processing and check their value

  Scenario: Use default jpeg and png quality
    Given I login as administrator
    And go to System/Configuration
    And follow "System Configuration/General Setup/Upload Settings" on configuration sidebar
    And uncheck "Use default" for "PNG resize quality (%)" field
    And uncheck "Use default" for "JPEG resize quality (%)" field
    And should see "PNG resize quality (%)" with options:
      | Value              |
      | Preserve quality   |
      | Minimize file size |
    And I should see "Modification of the default value may cause temporary storefront slow-down until all product images are resized. The changes on the product listing page will not be applied immediately and will require manual start of the search re-index. Make sure that the harddrive has at least 50% space available as the resized images will be stored alongside the existing ones."
    When I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Update jpeg and png quality
    Given I go to System/Configuration
    And follow "System Configuration/General Setup/Upload Settings" on configuration sidebar
    When I fill in "JPEG resize quality (%)" with "30"
    And select "Minimize file size" from "PNG resize quality (%)"
    And submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check validation
    Given I go to System/Configuration
    And follow "System Configuration/General Setup/Upload Settings" on configuration sidebar
    When I fill in "JPEG resize quality (%)" with "29"
    And submit form
    Then I should see validation errors:
      | JPEG resize quality (%) | This value should be between 30 and 100. |

  Scenario: Restore jpeg and png quality
    When check "Use default" for "JPEG resize quality (%)" field
    And check "Use default" for "PNG resize quality (%)" field
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Disable image processing
    When uncheck "Use default" for "Enable Image Optimization" field
    And I uncheck "Enable Image Optimization"
    And I submit form
    Then I should not see "PNG resize quality (%)"
    And I should not see "JPEG resize quality (%)"

