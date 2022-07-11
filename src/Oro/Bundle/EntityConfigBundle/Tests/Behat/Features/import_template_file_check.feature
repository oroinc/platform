@regression
@ticket-BB-16260

Feature: Import template file check
  In order to effectively manage custom fields
  As an Administrator
  I want to be sure that generated import template have no errors

  Scenario: Check product custom fields template
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    When I download "user" extend entity Data Template file
    When I import downloaded template file
    Then Email should contains the following "Errors: 0" text
    When I reload the page
    Then I should see an "Update Schema" element
