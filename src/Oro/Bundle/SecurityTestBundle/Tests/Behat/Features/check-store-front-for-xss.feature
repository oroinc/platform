@fixture-OroSecurityTestBundle:shopping-list.yml
Feature: Store front MUST NOT contain XSS vulnerabilities on all accessible pages

  Scenario: Check store front for XSS vulnerability
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I should not see XSS at any page of "frontend urls"
    And I should not see alert