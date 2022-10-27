@regression
@ticket-BAP-19779

Feature: Check that the entity field type is not changed when changing field properties
  In order to be able to work correctly with entity fields and change their properties without changing the field type
  As an administrator
  I change the properties of some fields and check that the field type has not changed

  Scenario: Feature Background
    Given I login as administrator
    And go to System/Entities/Entity Management

  Scenario:
    Given I filter Name as is equal to "Product"
    And click View Product in grid
    When I click Edit sku in grid
    And fill form with:
      | Auditable             | No |
      | Use as Identity Field | No |
    And save and close form
    Then I should see "Field saved" flash message
    And should see following grid containing rows:
      | Name | Storage type |
      | sku  | Table column |
