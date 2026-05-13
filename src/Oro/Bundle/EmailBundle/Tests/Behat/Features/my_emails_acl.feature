@fixture-OroEmailBundle:my-emails.yml
@fixture-OroUserBundle:user.yml

Feature: My emails ACL
  In order to get access to my emails
  As an user
  I should not have access to emails i does not own

  Scenario: Check the admin have an access to emails
    Given I login as administrator
    When I click My Emails in user menu
    Then I should see following grid:
      | Subject               | Date       |
      | There is no spoon     | 2010-10-31 |
    When I click "View" on row "There is no spoon" in grid
    Then I should see "Charlie Sheen"
    And I should see "There is no spoon"
    And I remember current URL

  Scenario: Check the not admin user have no access to email by direct link
    Given I login as "charlie" user
    When I follow remembered URL
    Then I should not see "Charlie Sheen"
    And I should not see "There is no spoon"
