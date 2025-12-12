@regression
@fixture-OroUserBundle:user.yml
@fixture-OroUserBundle:user_without_organization_and_business_unit.yml

Feature: Check login user messages without organization or business unit

  Scenario: Check message when try to login user without organization
    When I login as "test1" user
    Then I should not see "The user does not have an active organization assigned to it. Please contact your administrator."

  Scenario: Check message when try to login user with organization without business unit
    When I login as "test" user
    Then I should see "The user must be assigned to at least one organization business unit in order to log in. Please contact your administrator."
