@regression
# TODO: unskip after fix in BAP-14958
@skip
Feature: Sales Flow
  In order to check Sales Flow on admin panel
  As a Admin
  I want to start end to end test

  Scenario: Lead disqualified – button
    Given login as administrator
    And go to Sales/ Leads
    And click "Create Lead"
    And fill form with:
      |Lead name|SalesFlowOff|
    And save and close form
    When click "Disqualify"
    Then should see "Disqualified"
    And go to Sales/ Leads
    And there is no records in grid

  Scenario: Lead disqualified – workflow
    Given go to System/ Workflow
    And click activate "Unqualified Sales Lead" in grid
    And click "Activate"
    And go to Sales/ Leads
    And click "Create Lead"
    And fill form with:
      |Lead name|SalesFlowOn|
    And save and close form
    When click "Disqualify"
    Then should see "Disqualified"
    And go to Sales/ Leads
    And there is no records in grid
    And go to System/ Workflow
    And click deactivate "Unqualified Sales Lead" in grid
    And click "Yes, Deactivate"

  Scenario: Lead disqualified – edit
    Given go to Sales/ Leads
    And click "Create Lead"
    And fill form with:
      |Lead name|SalesFlowEdit|
    And save and close form
    And click "Edit Lead"
    When fill form with:
    |Status|Disqualified|
    And save and close form
    Then should see "Disqualified"
    And go to Sales/ Leads
    And there is no records in grid

  Scenario: Lead qualified by pre-sales, then disqualified
    Given go to Sales/ Leads
    And click "Create Lead"
    And fill form with:
      |Lead name|SalesFlowQD|
    And save and close form
    And click "Edit Lead"
    And fill form with:
      |Status|Qualified|
    And save and close form
    When click "Disqualify"
    Then should see "Disqualified"
    And go to Sales/ Leads
    And there is no records in grid
    And click Logout in user menu

  Scenario: Lead created via form, qualified, lost – edit
    Given I am on the homepage
    And click "Contact Us"
    And fill form with:
    |First name              |FirstN          |
    |Last name               |LastN           |
    |Organization name       |TestOrganozation|
    |Preferred contact method|Email           |
    |Phone                   |0504006666      |
    |Email                   |testFL@test.com |
    |Contact reason          |Other           |
    |Comment                  |Test Comment    |
    And click "Submit"
    And login as administrator
    And go to Activities/ Contact Requests
    And click view "testFL@test.com" in grid
    And click "Convert to Lead"
    And click "Submit"
    And click "Edit Contact Request"
    And fill form with:
    |Preferred contact method|Both phone & email|
    And save and close form
    And go to Activities/ Calendar Events
    And click "Create Calendar event"
    When I fill "Event Form" with:
      | Title         | Sales Flow Event    |
      | Start         | 2017-01-24 12:00 AM |
      | End           | 2020-02-26 12:00 AM |
      | All-Day Event | true                |
      | Repeat        | false               |
      | Description   | Flow Event desc     |
      | Guests        | John Doe            |
      | Color         | Cornflower Blue     |
      | Context       | FirstN LastN (Lead) |
    And set Reminders with:
      | Method        | Interval unit | Interval number |
      | Email         | days          | 1               |
      | Flash message | minutes       | 30              |
    And I save and close form
    And click "Notify"
    And go to Customers/ Accounts
    And click "Create Account"
    And fill form with:
    |Account Name|SalesFlowAccount|
    And save and close form
    When go to Sales/ Leads
    And click view "testFL@test.com" in grid
    And click "Convert to Opportunity"
    And fill form with:
    |Account|SalesFlowAccount|
    And save and close form
    And click "Edit Opportunity"
    And fill "Opportunity Form" with:
    | Budget Amount     |1000        |
    And click on "Select Expected Close Date"
    And click on "Today"
    And save and close form
    And click "Edit Opportunity"
    When fill form with:
      |Status      |Closed Lost|
      |Close Reason|Outsold    |
    And save and close form
    Then should see "Closed Lost"
    And go to Sales/ Opportunities
    And there is no records in grid

  Scenario: Lead imported, qualified, lost – board
    Given go to System/ Channels
    And click edit "Commerce channel" in grid
    And fill "Channel Form" with:
    |Entities Select|Business Customer|
    And click "Add"
    And save and close form
    And go to Customers/ Business Customers
    And click "Create Business Customer"
    And fill form with:
    |Account      |SalesFlowAccount|
    |Customer Name|NewBCustomer    |
    And save and close form
    And go to Sales/ Leads
    And I download "Lead" Data Template file
    And I fill template with data:
    |Lead name |First name|Last name|Status Id|Company name|Emails 1 Email    |
    |ImportLead|FirstIN   |LastIN   |New      |TetsICompany|ImporLead@test.com|
    And I import file
    And I reload the page
    And click view "ImporLead@test.com" in grid
    And click "Convert to Opportunity"
    And fill form with:
    |Account|SalesFlowAccount (Account)|
    And save and close form
    And click "Edit Opportunity"
    And fill "Opportunity Form" with:
    | Budget Amount     |1000      |
    And click on "Select Expected Close Date"
    And click on "Today"
    And save and close form
    And go to Sales/ Opportunities
    And click on "View selector"
    And click "Board"
    And drag and drop "ImportLead" on "Closed Lost"
    Then should see "Record has been succesfully updated" flash message
    And go to Sales/ Opportunities
    And there is no records in grid
