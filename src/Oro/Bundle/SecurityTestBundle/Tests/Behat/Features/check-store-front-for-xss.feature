@fixture-OroSecurityTestBundle:shopping-list.yml
Feature: Store front MUST NOT contain XSS vulnerabilities on all accessible pages

  Scenario: Check store front for XSS vulnerability
    Given I signed in as AmandaRCole@example.org on the store frontend
#    And I set "Test Web Catalog" as default web catalog
    When I visiting pages listed in "frontend profile urls"
    Then I should not get XSS vulnerabilities
#    When I visiting pages listed in "frontend product urls"
#    Then I should not get XSS vulnerabilities
