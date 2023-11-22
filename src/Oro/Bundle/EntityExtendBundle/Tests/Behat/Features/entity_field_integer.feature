@ticket-BAP-19774
@regression

Feature: Entity field integer
  As an administrator, I want to add fields of type 'smallint', 'int', 'bigint', make sure that validation and
  data-grid filters work correctly.

  Scenario: Feature Background
    Given I login as administrator
    And go to System/Entities/Entity Management
    And filter Name as is equal to "Organization"
    And click View Organization in grid

  Scenario Outline: Prepare fields
    Given I click on "Create Field"
    And fill form with:
      | Field name | <Name> |
      | Type       | <Type> |
    And click "Continue"
    And save and close form
    Examples:
      | Name           | Type     |
      | smallint_field | SmallInt |
      | integer_field  | Integer  |
      | bigint_field   | BigInt   |

  Scenario: Update schema
    Given I click update schema
    Then I should see Schema updated flash message

  Scenario: Update fields
    When I go to System/User Management/Organizations
    And click Edit ORO in grid
    When I fill form with:
      | Smallint_field | 32768            |
      | Integer_field  | 2147483648       |
      | Bigint_field   | 9007199254740992 |
    And save form
    Then I should see validation errors:
      | Smallint_field | This value should be between -32,768 and 32,767.                               |
      | Integer_field  | This value should be between -2,147,483,648 and 2,147,483,647.                 |
      | Bigint_field   | This value should be between -9,007,199,254,740,991 and 9,007,199,254,740,991. |

    When I fill form with:
      | Smallint_field | -32769            |
      | Integer_field  | -2147483649       |
      | Bigint_field   | -9007199254740992 |
    And save form
    Then I should see validation errors:
      | Smallint_field | This value should be between -32,768 and 32,767.                               |
      | Integer_field  | This value should be between -2,147,483,648 and 2,147,483,647.                 |
      | Bigint_field   | This value should be between -9,007,199,254,740,991 and 9,007,199,254,740,991. |

    When I fill form with:
      | Smallint_field | 32767            |
      | Integer_field  | 2147483647       |
      | Bigint_field   | 9007199254740991 |
    And save form
    Then I should see "Organization saved" flash message
