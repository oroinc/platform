@ticket-BAP-13571
@automatically-ticket-tagged
@not-automated
@draft
Feature: Workflow parameters
   In order to customize workflow parameters from the UI
   As an Developer
   I want to configure workflow parameters

  Scenario: Feature background
    Given there are following User:
      | Username | Roles                 |
      | OroAdmin | Administrator         |
      | DevLead  | Leads Development Rep |
    And "Send Email Campaign" workflow exists
    And there is following Email Template:
      | Template Name | Entity Name | Subject        | Content        |
      | WF Mail       | Contact     | Test Subject   | Test Content   |
      | WF Mail 2     | Contact     | Test Subject 2 | Test Content 2 |
    And there is following Contact:
      | First Name | Last Name | Emails          |
      | Bill       | Toe       | billtoe@goo.com |
      | Jess       | Lang      | jessl@text.you  |
    And Magento integration is set up

  Scenario: DevLead creates workflow
    Given I login as "DevLead" user
    And I go to System/Workflows
    And I click on "Create Workflow"
    And I fill in the following:
      | Name          | Related Entity |
      | Send Email WF | Contact        |
    And I click "Add Step"
    And I fill in "Name" with "Send Email"
    And I click on "Apply"
    When I click on "Add Transition"
    And click on "Send Email" transition
    And I open "Configuration" tab
    And I fill in the following:
      | Email Template | Sendout Delay |
      | WF Mail        | 1d 1h 1m      |
    And I click on "Apply"
    And I click on "Save and Close"
    Then changes should be save successfully.

  Scenario: DevLead activates workflow
    Given I go to System/Workflows
    When I click on "Send Email WF"
    And I click on "Activate"
    And I submit form
    Then "Active" status should be displayed

  Scenario: DevLead executes workflow on Contact
    Given I go to Customers/Contacts
    And I click on "Bill Toe"
    When I click on "Send Email"
    And I submit form
    Then email should be sent to "Bill Toe" email

  Scenario: DevLead extends workflow
    Given I go to System/Workflows
    And I click on "Send Email WF"
    And I click on "Edit"
    And I click "Add Step"
    And I fill in "Name" with "Send Email Secondly"
    And I click on "Apply"
    When I click on "Add Transition"
    And click on "Send Email Secondly" transition
    And I open "Configuration" tab
    And I fill in the following:
      | Email Template | Sendout Delay |
      | WF Mail 2      | 2d 2h 2m      |
    And I click on "Apply"
    And I click on "Save and Close"
    Then changes should be save successfully

  Scenario: DevLead executes workflow on Contact again
    Given I go to Customers/Contacts
    And I click on "Bill Toe"
    When I click on "Send Email Secondly"
    And I submit form
    Then second email should be sent to "Bill Toe" email

  Scenario: DevLead configures workflow
    Given I go to System/Workflows
    And I click on "Send Email WF"
    And I click on "SOME"
    #should be clarified
    And I fill in the following:
      | Website | SMTP Provider | Send Email                       |
      | Magento | Google        | After first one  has been opened |
    When I click on "Save"
    Then I should be on Send Email WF page
    And changes should be save successfully

  Scenario: DevLead executes changed workflow
    Given I go to Customers/Contacts
    And I click on "Jess Lang"
    When I click on "Send Email"
    And I submit form
    Then email should be sent to "Jess Lang" email
    But I go to Customers/Contacts
    And I click on "Jess Lang"
    And I click on "Send Email Secondly"
    And I submit form
    Then email should not be sent to "Jess Lang" email
    But I open first sent email in the mailbox
    Then second email should be sent to "Jess Lang" email
