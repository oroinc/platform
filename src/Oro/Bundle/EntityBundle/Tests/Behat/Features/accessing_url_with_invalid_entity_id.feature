@ticket-BAP-18446
Feature: Accessing url with invalid entity id
  In order to be able to use UI without 500 errors
  As an user
  I want to see 404 page when database exception happened

  Scenario: Check out of range id
    Given I login as administrator
    When I open User view page with id 123456789123456789
    Then I should see "404. Not Found"
