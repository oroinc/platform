@regression
@ticket-BAP-14053
@automatically-ticket-tagged
@fixture-OroConfigBundle:custom_report.yml
@fixture-OroConfigBundle:activities.yml
Feature: Display settings manage
  In order to control system display behavior
  As Administrator
  I need to be able to change display settings parameters

  Scenario: Prepare sessions
    Given sessions active:
      | Config | first_session  |
      | Check  | second_session |

  Scenario: Create Check session
    Given I proceed as the Check
    And I login as administrator

  Scenario: Create Config session
    Given I proceed as the Config
    And I login as administrator

    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar

  Scenario: Hide recent emails in user bar
    Given I proceed as the Config
    And I should see an "Recent Emails" element
    When I fill "System Config Form" with:
      | Show recent emails | false |
    And I save form
    Then I should not see an "Recent Emails" element

  Scenario: Disable WYSIWYG editor
    When I fill "System Config Form" with:
      | Enable WYSIWYG editor | true |
    And save form

    When I proceed as the Check
    And I go to Activities/Calendar Events
    And click "Create Calendar event"
    Then I should see an "WYSIWYG editor" element

    When I proceed as the Config
    And I fill "System Config Form" with:
      | Enable WYSIWYG editor | false |
    And save form

    When I proceed as the Check
    And I reload the page
    Then I should not see an "WYSIWYG editor" element

  Scenario: Change records in grid per page amount
    Given I proceed as the Check
    And I go to Activities/ Calendar Events
    Then per page amount should be 25
    And records in grid should be 25

    When I proceed as the Config
    And I fill "System Config Form" with:
      | Items per Page by Default | 10 |
    And I save form

    Given I proceed as the Check
    # I'm at Activities/ Calendar Events
    And I reload the page
    Then per page amount should be 10
    And records in grid should be 10

  Scenario: Make grid header sticky
    # I'm at Activities/ Calendar Events
    Given I see that grid has scrollable header

    When I proceed as the Config
    And I fill "System Config Form" with:
      | Lock headers in grids | false |
    And I save form

    Given I proceed as the Check
    # I'm at Activities/ Calendar Events
    And I reload the page
    Then I see that grid header is sticky

  Scenario: Disable navigation through grid entity from a view page
    # I'm at Activities/ Calendar Events
    Given I click view 1 in grid
    Then I should see an "Entity pagination" element

    When I proceed as the Config
    And I fill "System Config Form" with:
      | Record Pagination | false |
    And I save form

    When I proceed as the Check
    # I'm at Activities/ Calendar Events view 1
    And I reload the page
    Then I should not see an "Entity pagination" element

  Scenario: Change record pagination limit
    Given I proceed as the Config
    And I fill "System Config Form" with:
      | Record Pagination | on |
    And I save form

    When I proceed as the Check
    # I'm at Activities/ Calendar Events view 1
    And I reload the page
    Then I should see an "Entity pagination" element

    When I proceed as the Config
    And I fill "System Config Form" with:
      | Record Pagination limit | 20 |
    And I save form

    When I proceed as the Check
    And I go to Activities/ Calendar Events
    And I click view 1 in grid
    Then I should not see an "Entity pagination" element

  Scenario: Change activity list configuration
    Given I go to System/ User Management/ Users
    And I filter Username as is equal to "charlie"
    And I click view charlie in grid
    Then there is 10 records in activity list
    And Activity List must be sorted descending by updated date

    When I proceed as the Config
    And I fill "System Config Form" with:
      | Sort direction            | Ascending |
      | Items Per Page By Default | 25        |
    And I save form

    When I proceed as the Check
    # I'm at View Charlie User page
    And I reload the page
    Then there is 25 records in activity list
    And Activity List must be sorted ascending by updated date

    When I proceed as the Config
    And I fill "System Config Form" with:
      | Sort by field             | Created date |
      | Sort direction            | Descending   |
      | Items Per Page By Default | 10           |
    And I save form

    When I proceed as the Check
    # I'm at View Charlie User page
    And I reload the page
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
    When I proceed as the Config
    Then right sidebar is visible
    When I fill "System Config Form" with:
      | Enable left sidebar  | Yes |
      | Enable right sidebar | No  |
    And save form
    Then right sidebar is out of sight
    And left sidebar is visible
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

    When I proceed as the Check
    And go to Activities/ Calendar Events
    And click "Create Calendar event"
    Then I should see following available "Event Form" colors:
      | Apple green, Cornflower Blue, Mercury, Melrose, Mauve, Alizarin Crimson, Aqua, Aquamarine, Azure, Beige, Black |
    When I click My Calendar in user menu
    And click "My Calendar Choose Color Menu"
    Then I should see following available "Calendar" colors:
      | Alizarin Crimson, Beige, Black, Lime, Melrose, Mercury, Apple green, Cornflower Blue, Mauve, Aqua, Aquamarine, Azure |

  Scenario: Change taxonomy color settings
    When I proceed as the Config
    And fill "System Config Form" with:
      | Taxonomy Colors | Cornflower Blue, Mercury, Melrose, Mauve, Alizarin Crimson, Aqua, Aquamarine, Azure, Beige, Black, Lime |
    And save form

    When I proceed as the Check
    And go to System/Tags Management/Taxonomies
    And click "Create Taxonomy"
    Then I should see following available "TaxonomyForm" colors:
      | Cornflower Blue, Mercury, Melrose, Mauve, Alizarin Crimson, Aqua, Aquamarine, Azure, Beige, Black, Lime |

  Scenario: Change reports/segments settings
    Given I go to Reports & Segments/Calendar Events/Test Report
    Then I should not see "Show SQL Query"
    When I go to Reports & Segments/ Manage Segments
    And I click view "Featured Products" in grid
    Then I should not see "Show SQL Query"
    When I proceed as the Config
    And fill "System Config Form" with:
      | Display SQL in Reports and Segments | true |
    And save form
    When I proceed as the Check
    And I go to Reports & Segments/Calendar Events/Test Report
    Then I should see "Show SQL Query"
    When I go to Reports & Segments/ Manage Segments
    And I click view "Featured Products" in grid
    Then I should see "Show SQL Query"
