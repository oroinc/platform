@ticket-BAP-21510
@fixture-OroUserBundle:user.yml

Feature: Generate Key for user
  In order to create users
  As a OroCRM Admin user
  I need to be able to open Create User dialog and create new user

  Scenario: Generate Key for user
    Given I enable API
    And I login as administrator
    And I go to System/User Management/Users
    And I click View Charlie in grid
    And I click "Generate Key"
    And I should see "Generate key was successful. New key: "
