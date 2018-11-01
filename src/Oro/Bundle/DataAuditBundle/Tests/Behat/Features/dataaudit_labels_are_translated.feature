@ticket-BAP-17600
@fixture-OroUserBundle:user.yml
Feature:  DataAudit labels are translated
  In order to get information about changed entity fields
  As a  Admin user
  I need to be able to see entity labels translated in data audit grid

  Scenario:
    Given I login as administrator
    And I go to System/User Management/Users
    And I click "Edit" on row "Charlie" in grid
    And I fill form with:
      | Username          | charlie-up        |
      | First Name        | FNUP              |
      | Last Name         | LNUP              |
      | Primary Email     | email-up@test.com |
    And save and close form
    When I go to System/ Data Audit
    Then I should see "Username:" in grid
    And I should see "First Name:" in grid
    And I should see "Last Name:" in grid
    And I should see "Primary Email:" in grid
