@regression

Feature: Theme Configuration

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create Theme Configuration
    Given I proceed as the Admin
    And I login as administrator
    When I go to System / Theme Configurations
    Then Page title equals to "All - Theme Configurations - System"
    And I should see "System / Theme Configurations" in breadcrumbs
    When I click "Create Theme Configuration"
    Then "Theme Configuration Form" must contains values:
      | Owner                                         | Main                                  |
      | Theme                                         | Refreshing Teal                       |
      | Name                                          |                                       |
      | Description                                   |                                       |
      | Type                                          | Storefront                            |
      | Promotional Content                           | Choose Content Block                  |
      | Top Navigation Menu                           | Choose a top navigation menu          |
      | Quick Links Menu                              | commerce_quick_access_refreshing_teal |
      | Quick Access Button Type                      | None                                  |
      | Language and Currency Switchers               | Always in the "hamburger" menu        |
      | Standalone Main Menu                          | false                                 |
      | Search On Smaller Screens                     | Integrated                            |
      | Page Template                                 |                                       |
      | Filter Panel Position on Product Listing Page | top                                   |
    And Page title equals to "Create Theme Configuration - Theme Configurations - System"
    And I should see "System / Theme Configurations" in breadcrumbs
    When I fill "Theme Configuration Form" with:
      | Name                                          | New Theme Configuration |
      | Theme                                         | default                 |
      | Description                                   | Default Description     |
      | Type                                          | Storefront              |
      | Top Navigation Menu                           | frontend_menu           |
      | Quick Links Menu                              | frontend_menu           |
      | Quick Access Button Label                     | Quick access button     |
      | Quick Access Button Type                      | menu                    |
      | Quick Access Button Frontend Menu             | frontend_menu           |
      | Standalone Main Menu                          | true                    |
      | Search On Smaller Screens                     | integrated              |
      | Page Template                                 | wide                    |
      | Filter Panel Position on Product Listing Page | sidebar                 |
    And I save and close form
    Then I should see "Theme Configuration" flash message

  Scenario: Check Values Of Created Theme Configuration
    Given I go to System / Theme Configurations
    When I click "Edit" on row "New Theme Configuration" in grid
    Then "Theme Configuration Form" must contains values:
      | Owner                                         | Main                    |
      | Name                                          | New Theme Configuration |
      | Theme                                         | Refreshing Teal         |
      | Description                                   | Default Description     |
      | Type                                          | Storefront              |
      | Top Navigation Menu                           | frontend_menu           |
      | Quick Links Menu                              | frontend_menu           |
      | Quick Access Button Label                     | Quick access button     |
      | Quick Access Button Type                      | Frontend Menu           |
      | Quick Access Button Frontend Menu             | frontend_menu           |
      | Standalone Main Menu                          | true                    |
      | Search On Smaller Screens                     | Integrated              |
      | Page Template                                 | wide                    |
      | Filter Panel Position on Product Listing Page | sidebar                 |
    And I click "Cancel"

  Scenario: Update Theme Configuration
    Given I go to System / Theme Configurations
    When I click "Edit" on row "Refreshing Teal" in grid
    Then the "Theme" field should be disabled in form "Theme Configuration Form"
    When I fill "Theme Configuration Form" with:
      | Description                                   | Default Description Updated |
      | Type                                          | Storefront                  |
      | Top Navigation Menu                           | commerce_top_nav            |
      | Quick Links Menu                              | commerce_top_nav            |
      | Quick Access Button Type                      | menu                        |
      | Quick Access Button Label                     | Quick access button         |
      | Quick Access Button Frontend Menu             | commerce_top_nav            |
      | Language and Currency Switchers               | always_in_hamburger_menu    |
      | Standalone Main Menu                          | false                       |
      | Search On Smaller Screens                     | standalone                  |
      | Page Template                                 | tabs                        |
      | Filter Panel Position on Product Listing Page | top                         |
    And I save and close form
    Then I should see "Theme Configuration" flash message

  Scenario: Check Values Of Updated Theme Configuration
    Given I go to System / Theme Configurations
    When I click "Edit" on row "Refreshing Teal" in grid
    Then "Theme Configuration Form" must contains values:
      | Owner                                         | Main                           |
      | Name                                          | Refreshing Teal                |
      | Description                                   | Default Description Updated    |
      | Type                                          | Storefront                     |
      | Top Navigation Menu                           | commerce_top_nav               |
      | Quick Links Menu                              | commerce_top_nav               |
      | Quick Access Button Type                      | Frontend Menu                  |
      | Quick Access Button Label                     | Quick access button            |
      | Quick Access Button Frontend Menu             | commerce_top_nav               |
      | Language and Currency Switchers               | Always in the "hamburger" menu |
      | Standalone Main Menu                          | false                          |
      | Search On Smaller Screens                     | Standalone                     |
      | Page Template                                 | tabs                           |
      | Filter Panel Position on Product Listing Page | top                            |
    And click "Cancel"

  Scenario: Check Quick Access Button Field Behavior
    Given I go to System / Theme Configurations
    When I click "Create Theme Configuration"
    Then I should not see "Quick Access Button Label Input" element inside "Quick Access Button Field" element
    And I should not see "Quick Access Button Frontend Menu Input" element inside "Quick Access Button Field" element
    When I fill "Theme Configuration Form" with:
      | Quick Access Button Type | menu |
    Then I should see "Quick Access Button Label Input" element inside "Quick Access Button Field" element
    And I should see "Quick Access Button Frontend Menu Input" element inside "Quick Access Button Field" element
    And click "Cancel"

  Scenario: Change Top Navigation Menu to "frontend_menu"
    Given I go to System / Theme Configurations
    When I click "Edit" on row "Refreshing Teal" in grid
    And I fill "Theme Configuration Form" with:
      | Top Navigation Menu | frontend_menu |
    And I save form
    Then I should see "Theme Configuration" flash message

  Scenario: Check "frontend_menu" in home page
    Given I proceed as the Buyer
    When I am on homepage
    Then I should see "TopRightBar" element with text "My Account" inside "Header" element
    And I should see "TopRightBar" element with text "My Profile" inside "Header" element
    And I should see "TopRightBar" element with text "Personal Address" inside "Header" element
    And I should see "TopRightBar" element with text "Company Address" inside "Header" element
    And I should see "TopRightBar" element with text "Address Book" inside "Header" element
    And I should see "TopRightBar" element with text "Shopping Lists" inside "Header" element
    And I should see "TopRightBar" element with text "Catalog" inside "Header" element

  Scenario: Change Top Navigation Menu to "frontend_menu"
    Given I proceed as the Admin
    When I go to System / Theme Configurations
    And I click "Edit" on row "Refreshing Teal" in grid
    And I fill "Theme Configuration Form" with:
      | Top Navigation Menu | commerce_top_nav |
    And I save form
    Then I should see "Theme Configuration" flash message

  Scenario: Check "frontend_menu" in home page
    Given I proceed as the Buyer
    When I am on homepage
    Then I should see "TopRightBar" element with text "1-800-555-5555" inside "Header" element
    And I should see "TopRightBar" element with text "Live Chat" inside "Header" element
    And I should see "TopRightBar" element with text "Fast & Free Shipping" inside "Header" element
    And I should not see "TopRightBar" element with text "My Account" inside "Header" element
    And I should not see "TopRightBar" element with text "My Profile" inside "Header" element

  Scenario: Change Quick Access Button
    Given I proceed as the Admin
    When I go to System / Theme Configurations
    And I click "Edit" on row "Refreshing Teal" in grid
    And I fill "Theme Configuration Form" with:
      | Top Navigation Menu               | frontend_menu               |
      | Quick Access Button Label         | Changed Quick access button |
      | Quick Access Button Type          | menu                        |
      | Quick Access Button Frontend Menu | commerce_top_nav            |
    And I save form
    Then I should see "Theme Configuration" flash message

  Scenario: Check "Change Quick Access Button" in home page
    Given I proceed as the Buyer
    When I am on homepage
    Then I should see "Changed Quick access button"
    When I click "Changed Quick access button"
    Then I should see "Quick Access Button Menu" element with text "1-800-555-5555" inside "Header" element
    And I should see "Quick Access Button Menu" element with text "Live Chat" inside "Header" element
    And I should see "Quick Access Button Menu" element with text "Fast & Free Shipping" inside "Header" element

  Scenario: Check "Standalone Main Menu" should not be in home page
    Given I am on homepage
    Then I should not see an "Standalone Main Menu" element

  Scenario: Change Standalone Main Menu
    Given I proceed as the Admin
    And I go to System / Theme Configurations
    And I click "Edit" on row "Refreshing Teal" in grid
    When I fill "Theme Configuration Form" with:
      | Standalone Main Menu | true |
    And I save form
    Then I should see "Theme Configuration" flash message

  Scenario: Check "Standalone Main Menu" in home page
    Given I proceed as the Buyer
    When I am on homepage
    Then I should see an "Standalone Main Menu" element

  Scenario: Check Standalone "Search On Smaller Screens" in home page
    Given I am on homepage
    Then I should see an "Search Widget Standalone" element
    And I should not see an "Search Widget Integrated" element

  Scenario: Change Search On Smaller Screens
    Given I proceed as the Admin
    When I go to System / Theme Configurations
    And I click "Edit" on row "Refreshing Teal" in grid
    And I fill "Theme Configuration Form" with:
      | Search On Smaller Screens | integrated |
    And I save form
    Then I should see "Theme Configuration" flash message

  Scenario: Check Integrated "Search On Smaller Screens" in home page
    Given I proceed as the Buyer
    When I am on homepage
    Then I should see an "Search Widget Integrated" element
    And I should not see an "Search Widget Standalone" element
