@regression
@ticket-CRM-8143
Feature: Email Navigation Items
  In order to manage Email feature
  as an Administrator
  I should be able to see or not see navigation items based on feature state

  Scenario: Scenario background
    Given Charlie Sheen active user exists in the system
    And I login as "charlie" user
    And I click My Emails in user menu
    And there are no records in grid
    When I click "Compose"
    And fill "Email Form" with:
      | Body    | Body 1            |
      | Subject | Subject 1         |
      | To      | John Doe          |
    And I click "Send"

  Scenario: Pin and Add to favorites Email pages
    Given I pin page
    When I add page to favorites
    Then "My Emails - Charlie Sheen" link must be in pin holder
    And Favorites must contain "My Emails - Charlie Sheen"
    When I click View Subject 1 in grid
    And pin page
    And add page to favorites
    Then "Subject 1 - My Emails" link must be in pin holder
    And Favorites must contain "Subject 1 - My Emails"

  Scenario: Disable feature and validate links
    Given I go to Dashboards/Dashboard
    When I disable Email feature
    And I reload the page
    Then "My Emails - Charlie Sheen" link must not be in pin holder
    And Favorites must not contain "My Emails - Charlie Sheen"
    And "Subject 1 - My Emails" link must not be in pin holder
    And Favorites must not contain "Subject 1 - My Emails"

  Scenario: Re-Enable feature and validate links
    Given I go to Dashboards/Dashboard
    When I enable Email feature
    And I reload the page
    Then "My Emails - Charlie Sheen" link must be in pin holder
    And Favorites must contain "My Emails - Charlie Sheen"
    And "Subject 1 - My Emails" link must be in pin holder
    And Favorites must contain "Subject 1 - My Emails"
