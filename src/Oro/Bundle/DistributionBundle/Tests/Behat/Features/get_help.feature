@fix-BB-12458
Feature: Help link
  I order to find help
  As a developer
  I want to see the correct links to the documentation

  Scenario: Check link
    Given I am on "/install.php/login"
    And I should see "Oro Installer"
    When I click on "Get help"
    Then the documentation for installer will opened
