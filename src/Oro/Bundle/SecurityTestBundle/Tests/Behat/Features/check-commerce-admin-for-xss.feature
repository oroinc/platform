@fixture-OroSecurityTestBundle:user.yml
@fixture-OroSecurityTestBundle:shipping-method.yml
@fixture-OroSecurityTestBundle:shipping-method-rule.yml
@fixture-OroSecurityTestBundle:payment-method.yml
@fixture-OroSecurityTestBundle:payment-method-rule.yml
@fixture-OroSecurityTestBundle:commerce-fixtures.yml
@fixture-OroSecurityTestBundle:order.yml
@fixture-OroSecurityTestBundle:web-catalog.yml
Feature: Commerce admin area MUST NOT contain XSS vulnerabilities on all accessible pages

  Scenario: Create admin session
    Given I login to admin area as fixture user "xss_user"

  Scenario: Check backend sales pages for XSS vulnerability
    When I visiting pages listed in "backend sales urls"
    Then I should not get XSS vulnerabilities

  Scenario: Check backend customers pages for XSS vulnerability
    When I visiting pages listed in "backend customers urls"
    Then I should not get XSS vulnerabilities

  Scenario: Check backend marketing pages for XSS vulnerability
    When I visiting pages listed in "backend marketing urls"
    Then I should not get XSS vulnerabilities
