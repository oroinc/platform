@ticket-BAP-18893
@regression
Feature: Correct create schema with deleted new field
  In order to have correct schema when we have deleted new field (without update schema)
  As administrator
  I need to be able to create new field for some entity, delete this field and update schema and have correct schema

  Scenario: Delete new field and update schema and check schema
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
    And I click remove "Test1" in grid
    And click "Yes"
    Then click update schema
    And check if field "test1_ss" "not" in db table by entity class "Oro\Bundle\UserBundle\Entity\User"

  Scenario: Restore field and update schema and check schema
    Given I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    And I click restore "Test1" in grid
    Then click update schema
    And check if field "test1_ss" "is" in db table by entity class "Oro\Bundle\UserBundle\Entity\User"

  # field witch one time was created with updated schema should be in schema when deleted it
  Scenario: Delete field and update schema and check schema
    Given I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    And I click remove "Test1" in grid
    And click "Yes"
    Then click update schema
    And check if field "test1_ss" "is" in db table by entity class "Oro\Bundle\UserBundle\Entity\User"
