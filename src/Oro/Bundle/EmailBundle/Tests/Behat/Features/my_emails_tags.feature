@regression
@ticket-BAP-20724
@fixture-OroEmailBundle:my-emails.yml
@fixture-OroTagBundle:TagFixture.yml

Feature: My emails tags
  In order to manage my emails
  As an administrator
  I need to be able to enable tags

  Scenario: Enable tags
    Given I login as administrator
    Given I go to System/Entities/Entity Management
    And filter Name as is equal to "EmailUser"
    When I click Edit EmailUser in grid
    And select "Yes" from "Enable Tags"
    And save and close form
    Then I should see "Entity saved" flash message

  Scenario: Edit tag in grid
    And I click My Emails in user menu
    When I edit Tags as "Premium"
    Then should see "Record has been successfully updated" flash message
    When I reload the page
    Then should see following grid:
      | Subject           | Tags    |
      | There is no spoon | Premium |
