@fixture-OroSecurityTestBundle:frontend-fixtures.yml
Feature: Store front MUST NOT contain XSS vulnerabilities on all accessible pages

  Scenario: Check store front profile pages for XSS vulnerability
    Given sessions active:
      | Admin          |first_session |
      | User           |second_session|
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    When I visiting pages listed in "frontend profile urls"
    Then I should not get XSS vulnerabilities

  Scenario: Check store front checkout related for XSS vulnerability
    Given I proceed as the Admin
    And login as administrator
    # Enable access to RFQ edit form
    And I go to System/ Workflows
    And I click Deactivate "RFQ Submission Flow" in grid
    And I click "Yes, Deactivate"
    # Make quote available at store front
    Then I go to Sales/ Quotes
    And I click "Send to Customer" on first row in grid
    And I click "Send"
    Then I proceed as the User
    When I visiting pages listed in "frontend order related urls"
    Then I should not get XSS vulnerabilities

  Scenario: Check store front catalog pages for XSS vulnerability
    Given I proceed as the Admin
    And I go to System/ Configuration
    And follow "Commerce/Catalog/Special Pages" on configuration sidebar
    And uncheck "Use default" for "Enable all products page" field
    And I check "Enable all products page"
    And save form
    Then I should see "Configuration saved" flash message
    When I proceed as the User
    And I visiting pages listed in "frontend catalog urls"
    Then I should not get XSS vulnerabilities

  Scenario: Check store front product view pages with default template for XSS vulnerability
    Given I proceed as the User
    When I visiting pages listed in "frontend product view urls"
    Then I should not get XSS vulnerabilities

  Scenario: Check store front product view pages with Short Page for XSS vulnerability
    Given I proceed as the Admin
    And I follow "Commerce/Design/Theme" on configuration sidebar
    And fill "Page Templates Form" with:
      | Use Default  | false             |
      | Product Page | Short page        |
    And save form
    And I should see "Configuration saved" flash message
    When I proceed as the User
    And I visiting pages listed in "frontend product view urls"
    Then I should not get XSS vulnerabilities

  Scenario: Check store front product view pages with Two columns page for XSS vulnerability
    Given I proceed as the Admin
    And fill "Page Templates Form" with:
      | Use Default  | false             |
      | Product Page | Two columns page  |
    And save form
    And I should see "Configuration saved" flash message
    When I proceed as the User
    And I visiting pages listed in "frontend product view urls"
    Then I should not get XSS vulnerabilities

  Scenario: Check store front product view pages with List page for XSS vulnerability
    Given I proceed as the Admin
    And fill "Page Templates Form" with:
      | Use Default  | false             |
      | Product Page | List page         |
    And save form
    And I should see "Configuration saved" flash message
    When I proceed as the User
    And I visiting pages listed in "frontend product view urls"
    Then I should not get XSS vulnerabilities
