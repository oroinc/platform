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

  Scenario: Check store catalog pages front for XSS vulnerability
    Given I proceed as the Admin
    And login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Catalog/Special Pages" on configuration sidebar
    And uncheck "Use default" for "Enable all products page" field
    And I check "Enable all products page"
    And save form
    Then I should see "Configuration saved" flash message
    When I proceed as the User
    And I visiting pages listed in "frontend catalog urls"
    Then I should not get XSS vulnerabilities
