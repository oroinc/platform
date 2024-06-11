@ticket-BAP-18094

Feature: Notification Rules system configuration validation
  In order to configure Notification Rules
  As an Administrator
  I should be able to enter only valid data in system configuration

  Scenario: Check Notification Rules settings validation
    Given I login as administrator
    And I go to System/ Configuration
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    And uncheck "Use default" for "Sender Email" field in section "Notification Rules"
    And I type "" in "Sender Email" from "Notification Rules Section"
    And I click on empty space
    Then I should see "This value should not be blank"

    When I type "aaa" in "Sender Email" from "Notification Rules Section"
    And I click on empty space
    Then I should not see "This value should not be blank"
    And I should see "This value is not a valid email address"

    When I type "test@test.com" in "Sender Email" from "Notification Rules Section"
    And I click on empty space
    Then I should not see "This value is not a valid email address"

    When uncheck "Use default" for "Sender Name" field in section "Notification Rules"
    And I type "" in "Sender Name" from "Notification Rules Section"
    And I click on empty space
    Then I should see "This value should not be blank"

    When I type "TEST_NAME" in "Sender Name" from "Notification Rules Section"
    And I click on empty space
    Then I should not see "This value should not be blank"
