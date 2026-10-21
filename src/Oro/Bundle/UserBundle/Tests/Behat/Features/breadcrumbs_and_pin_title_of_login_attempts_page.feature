@regression
@ticket-BB-28044

Feature: Breadcrumbs and pin title of login attempts page
  In order to know where I am in the back-office
  As an administrator
  I want the Login Attempts page to show its own page title, breadcrumbs and pin title instead of the Users ones

  Scenario: Login Attempts page has its own page title, breadcrumbs and pin title
    Given I login as administrator
    When I go to System/User Management/Login Attempts
    Then Page title equals to "All - Login Attempts - User Management - System"
    And I should see "System/ User Management/ Login Attempts" in breadcrumbs
    When I pin page
    Then Login Attempts link must be in pin holder
    And Users link must not be in pin holder

  Scenario: Users page keeps its own page title and breadcrumbs
    When I go to System/User Management/Users
    Then Page title equals to "Active Users - Users - User Management - System"
    And I should see "System/ User Management/ Users" in breadcrumbs
