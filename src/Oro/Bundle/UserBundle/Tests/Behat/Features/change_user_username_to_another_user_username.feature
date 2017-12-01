@regression
@fixture-OroUserBundle:user.yml
Feature: Change user username to another user username
  ToDo: BAP-16103 Add missing descriptions to the Behat features

  Scenario: Customer user email change
    Given I login as "charlie" user
    And I click "Charlie Sheen"
    And I click "My User"
    And I click "Edit"
    And I fill form with:
      | Username | admin |
    And I save form
    Then I should see "This value is already used."
    And I click "Cancel"
    And I reload the page
    Then I should see "Users / Charlie Sheen"


