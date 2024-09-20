@ticket-BAP-11242
@automatically-ticket-tagged
@skip
#Unskip when BB-24668 will be fixed
Feature: Get help link
  I order to find help
  As crm user
  I need to have link to documentation

  Scenario: Click help link
    When I login as administrator
    Then I should see 'Get help' link with the url matches "http://www.oroinc.com/doc"
