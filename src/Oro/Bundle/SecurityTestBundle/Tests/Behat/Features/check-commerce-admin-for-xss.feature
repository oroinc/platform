@fixture-OroSecurityTestBundle:shipping-method.yml
@fixture-OroSecurityTestBundle:shipping-method-rule.yml
@fixture-OroSecurityTestBundle:payment-method.yml
@fixture-OroSecurityTestBundle:payment-method-rule.yml
@fixture-OroSecurityTestBundle:commerce-fixtures.yml
@fixture-OroSecurityTestBundle:order.yml
Feature: Commerce admin area MUST NOT contain XSS vulnerabilities on all accessible pages

  Scenario: Create admin session
    Given I login as administrator

  Scenario: Check store front profile pages for XSS vulnerability
    When I visiting pages listed in "backend sales urls"
    Then I should not get XSS vulnerabilities

  Scenario: Check store front profile pages for XSS vulnerability
    When I visiting pages listed in "backend customers urls"
    Then I should not get XSS vulnerabilities
