Feature: Get help link
  I order to find help
  As crm user
  I need to have link to documentation

  Scenario: Click help link
    Given I login as administrator
    When I click on "Help Icon"
    Then the documentation will opened
