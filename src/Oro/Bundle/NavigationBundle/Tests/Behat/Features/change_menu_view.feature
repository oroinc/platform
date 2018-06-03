@regression
@ticket-BAP-10860
@automatically-ticket-tagged
Feature: Change menu view
  In order to have best user experience for working with menu
  As an administrator
  I want to change menu view from configuration and navigate to menu

Scenario: Try navigate on top menu
  Given I login as administrator
  And menu is on the left side
  And I go to System/User Management/Users
  When click view John Doe in grid
  And username field should has admin value

Scenario: Change menu view
  Given I go to System/Configuration
  And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
  And uncheck "Use default" for "Position" field
  And select "Top" from "Position"
  When I save setting
  Then menu is at the top

Scenario: Try to navigate on left menu
  Given I go to System/User Management/Users
  When click view John Doe in grid
  Then username field should has admin value
