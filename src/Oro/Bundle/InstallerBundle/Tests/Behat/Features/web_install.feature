@ticket-CRM-7605
@automatically-ticket-tagged
@install
# Warning! This feature need a special install configuration
# with clear cache and setted "installed" parameter to false in parameters.yml
# with clear database
Feature: Web install
  In order to have install as simple as possible
  As a developer
  I need to have web install feature

  Scenario: Web install
    Given I am on homepage
    And I should see "Welcome to Oro Installer"
    And I press "Begin Installation"
    And I follow "Next"
    And I fill Configuration form according to my parameters.yml
    And I select "None" from "Transport"
    And I press "Next"
    And wait for Database initialization finish
    And I follow "Next"
    And I fill form with:
      | Organization name | ORO               |
      | Username          | admin             |
      | Password          | admin             |
      | Re-enter password | admin             |
      | Email             | admin@example.com |
      | First name        | John              |
      | Last name         | Doe               |
    When I press "Install"
    And wait for Installation finish
    And I follow "Next"
    Then I should be on the 5 step
    When I press Launch application button
    Then I should be on the admin login page

