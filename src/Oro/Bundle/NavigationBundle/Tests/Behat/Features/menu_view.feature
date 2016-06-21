Feature: Change menu view
  In order to have best user experience for working with menu
  As an administrator
  I want to change menu view from configuration and navigate to menu

Background:
  Given I login as "admin" user with "admin" password

Scenario: Try navigate on top menu
  Given menu is at the top
  And I open the menu "System -> User Management" and click "Users"
  When click view John Doe in grid
  And username field should have admin value

Scenario: Change menu view
  Given I open the menu "System" and click "Configuration"
  And follow "Display settings"
  And uncheck Use Default for "Position" field
  And select "Left" from "Position"
  When I save setting
  Then menu must be on left side

Scenario: Try to navigate on left menu
  Given menu is on the left side
  And I open the menu "System -> User Management" and click "Users"
  When click view John Doe in grid
  Then username field should have admin value
