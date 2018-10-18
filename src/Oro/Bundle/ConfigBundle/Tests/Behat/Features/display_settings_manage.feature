@regression
@ticket-BAP-14053
@automatically-ticket-tagged
@fixture-OroConfigBundle:custom_report.yml
@fixture-OroConfigBundle:activities.yml
Feature: Display settings manage
  In order to control system display behavior
  As Administrator
  I need to be able to change display settings parameters

  Scenario: Hide recent emails in user bar
    Given I login as administrator
    And I should see an "Recent Emails" element
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And I fill "System Config Form" with:
      | Show recent emails | false |
    And I save form
    Then I should not see an "Recent Emails" element

  Scenario: Disable WYSIWYG editor
    Given I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And I fill "System Config Form" with:
      | Enable WYSIWYG editor | true |
    And save form
    When I go to Activities/Calendar Events
    And click "Create Calendar event"
    Then I should see an "WYSIWYG editor" element
    But I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And I fill "System Config Form" with:
      | Enable WYSIWYG editor | false |
    And save form
    When I go to Activities/Calendar Events
    And click "Create Calendar event"
    Then I should not see an "WYSIWYG editor" element

  Scenario: Change records in grid per page amount
    Given I go to Activities/ Calendar Events
    Then per page amount should be 25
    And records in current page grid should be 25
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And I fill "System Config Form" with:
      | Items per Page by Default | 10 |
    And I save form
    And I go to Activities/ Calendar Events
    Then per page amount should be 10
    And records in current page grid should be 10

  Scenario: Make grid header sticky
    Given I go to Activities/ Calendar Events
    Then I see that grid has scrollable header
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And I fill "System Config Form" with:
      | Lock headers in grids | false |
    And I save form
    And I go to Activities/ Calendar Events
    Then I see that grid header is sticky

  Scenario: Disable navigation through grid entity from a view page
    Given I go to Activities/ Calendar Events
    And I click view 1 in grid
    Then I should see an "Entity pagination" element
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And I fill "System Config Form" with:
      | Record Pagination | false |
    And I save form
    And I go to Activities/ Calendar Events
    And click view 1 in grid
    Then I should not see an "Entity pagination" element

  Scenario: Change record pagination limit
    Given I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And I fill "System Config Form" with:
      | Record Pagination | on |
    And I save form
    And I go to Activities/ Calendar Events
    And I click view 1 in grid
    Then I should see an "Entity pagination" element
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And I fill "System Config Form" with:
      | Record Pagination limit | 20 |
    And I save form
    And I go to Activities/ Calendar Events
    And I click view 1 in grid
    Then I should not see an "Entity pagination" element

  Scenario: Change activity list configuration
    Given I go to System/ User Management/ Users
    And I click View Charlie in grid
    Then there is 10 records in activity list
    And Activity List must be sorted descending by updated date
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And I fill "System Config Form" with:
      | Sort direction            | Ascending |
      | Items Per Page By Default | 25        |
    And I save form
    And I go to System/ User Management/ Users
    And click View Charlie in grid
    Then there is 25 records in activity list
    And Activity List must be sorted ascending by updated date
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And I fill "System Config Form" with:
      | Sort by field             | Created date |
      | Sort direction            | Descending   |
      | Items Per Page By Default | 10           |
    And I save form
    And I go to System/ User Management/ Users
    And click View Charlie in grid
    Then I see following records in activity list with provided order:
      | -1 days |
      | -2 days |
      | -3 days |
      | -4 days |
      | -5 days |
      | -6 days |
      | -7 days |
      | -8 days |
      | -9 days |

  Scenario: Change sidebar settings
    Given right sidebar is visible
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And I fill "System Config Form" with:
      | Enable left sidebar  | Yes |
      | Enable right sidebar | No  |
    And save form
    Then right sidebar is out of sight
    And left sidebar is visible
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And I fill "System Config Form" with:
      | Enable left sidebar  | Yes |
      | Enable right sidebar | Yes |
    And save form
    Then left sidebar is visible
    And right sidebar is visible

  Scenario: Change calendar color settings
    Given I fill "System Config Form" with:
      | Event colors    | Apple green, Cornflower Blue, Mercury, Melrose, Mauve, Alizarin Crimson, Aqua, Aquamarine, Azure, Beige, Black, Lime |
      | Calendar colors | Alizarin Crimson, Beige, Black, Lime, Melrose, Mercury, Apple green, Cornflower Blue, Mauve, Aqua, Aquamarine, Azure |
    And save form
    And go to Activities/ Calendar Events
    And click "Create Calendar event"
    Then I should see following available "Event Form" colors:
      | Apple green, Cornflower Blue, Mercury, Melrose, Mauve, Alizarin Crimson, Aqua, Aquamarine, Azure, Beige, Black |
    When I click My Calendar in user menu
    And click "My Calendar Choose Color Menu"
    Then I should see following available "Calendar" colors:
      | Alizarin Crimson, Beige, Black, Lime, Melrose, Mercury, Apple green, Cornflower Blue, Mauve, Aqua, Aquamarine, Azure |

  Scenario: Change taxonomy color settings
    Given I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And fill "System Config Form" with:
      | Taxonomy Colors | Cornflower Blue, Mercury, Melrose, Mauve, Alizarin Crimson, Aqua, Aquamarine, Azure, Beige, Black, Lime |
    And save form
    And go to System/Tags Management/Taxonomies
    And click "Create Taxonomy"
    Then I should see following available "TaxonomyForm" colors:
      | Cornflower Blue, Mercury, Melrose, Mauve, Alizarin Crimson, Aqua, Aquamarine, Azure, Beige, Black, Lime |

  Scenario: Change reports settings
    Given I go to Reports & Segments/Calendar Events/Test Report
    Then I should not see "Show SQL Query"
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And fill "System Config Form" with:
      | Display SQL in Reports and Segments | true |
    And save form
    And I go to Reports & Segments/Calendar Events/Test Report
    Then I should see "Show SQL Query"
