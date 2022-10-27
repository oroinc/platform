@ticket-BAP-18893
@ticket-BAP-21015
@regression
Feature: Correct create schema with deleted new field
  In order to have correct schema when we have deleted new field (without update schema)
  As administrator
  I need to be able to create new field for some entity, delete this field and update schema and have correct schema

  Scenario: Delete new field w/o update schema and check schema
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    And I click on "Create Field"
    And I fill form with:
      | Field name    | Test1        |
      | Type          | Multi-Select |
    And I click "Continue"
    And I save and close form
    And I should see "Update schema"
    And I click remove "Test1" in grid
    And click "Yes"
    # any field in state NEW (that has not been applied against DB) will be deleted immediately w/o
    # requirement to update schema
    And I should not see "Update schema"
    And check if field "test1_ss" "not" in db table by entity class "Oro\Bundle\UserBundle\Entity\User"

  Scenario: Delete new field after update schema and check schema
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    And I click on "Create Field"
    And I fill form with:
      | Field name    | Test1        |
      | Type          | Multi-Select |
    And I click "Continue"
    And I save and close form
    And I should see "Update schema"
    Then I click "Update schema"
    And I click "Yes, Proceed"
    Then I should see Schema updated flash message
    And check if field "test1_ss" "is" in db table by entity class "Oro\Bundle\UserBundle\Entity\User"

  # field that was created with updated schema should be in schema even after deletion
  Scenario: Delete field and update schema and check schema
    Given I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    And I click remove "Test1" in grid
    And click "Yes"
    And I should see "Update schema"
    Then I click "Update schema"
    And I click "Yes, Proceed"
    Then I should see Schema updated flash message
    And check if field "test1_ss" "is" in db table by entity class "Oro\Bundle\UserBundle\Entity\User"

  Scenario: Restore field and update schema and check schema
    Given I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    And I click restore "Test1" in grid
    And I should see "Update schema"
    Then I click "Update schema"
    And I click "Yes, Proceed"
    Then I should see Schema updated flash message
    And check if field "test1_ss" "is" in db table by entity class "Oro\Bundle\UserBundle\Entity\User"
