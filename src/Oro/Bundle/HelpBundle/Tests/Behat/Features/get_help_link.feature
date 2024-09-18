@regression
@ticket-BAP-11242
@automatically-ticket-tagged
@skip
#Unskip when BB-24668 will be fixed
Feature: Get help link
  I order to find help
  As crm user
  I need to have link to documentation

  Scenario: Click help link
    Given I login as administrator
    When I click on "Help Icon"
    Then a new browser tab is opened and I switch to it
    And I should see "Welcome to Oro Documentation"
