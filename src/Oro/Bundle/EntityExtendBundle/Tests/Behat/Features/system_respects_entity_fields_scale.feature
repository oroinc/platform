@regression
@ticket-BB-20273

Feature: System respects entity fields scale
  In order to ensure that system respects scale settings and validate value according to them of number type fields
  As an Administrator
  I need to create fields with type decimal, float, percent and check how they render and accept the value with big scale

  Scenario: Login and enter to user entity management page
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid

  Scenario Outline: Create Percent and Float entity fields
    When I click "Create Field"
    And I fill form with:
      | Field Name   | <Field Name>   |
      | Storage Type | <Storage Type> |
      | Type         | <Type>         |
    And I click "Continue"
    And I fill form with:
      | Label                | <Label>            |
      | Add To Grid Settings | Yes and display    |
      | Show Grid Filter     | <Show Grid Filter> |
    And I save and close form
    Then I should see "Field saved" flash message

    Examples:
      | Field Name         | Storage Type     | Type     | Label              | Show Grid Filter |
      | column_percent     | Table column     | Percent  | Column Percent     | Yes              |
      | column_float       | Table column     | Float    | Column Float       | Yes              |
      | serialized_percent | Serialized field | Percent  | Serialized Percent | No               |
      | serialized_float   | Serialized field | Float    | Serialized Float   | No               |

  Scenario Outline: Create Decimal entity fields with Table Column type
    When I click "Create Field"
    And I fill form with:
      | Field Name   | <Field Name> |
      | Storage Type | Table column |
      | Type         | Decimal      |
    And I click "Continue"
    Then Precision field should has 10 value
    And Scale field should has 2 value
    When I fill form with:
      | Label                | <Label>         |
      | Add To Grid Settings | Yes and display |
      | Show Grid Filter     | Yes             |
      | Precision            | 7               |
      | Scale                | 10              |
    And I save and close form
    Then I should see validation errors:
      | Scale | This value should be between "0" and "7". |
    When I fill form with:
      | Precision | <Precision> |
      | Scale     | <Scale>     |
    And I save and close form
    Then I should see "Field saved" flash message

    Examples:
      | Field Name              | Label                   | Precision | Scale |
      | typical_column_decimal  | Typical Column Decimal  | 10        | 7     |
      | specific_column_decimal | Specific Column Decimal | 29        | 20    |

  Scenario: Create entity field with serialized Decimal type
    When I click "Create Field"
    And I fill form with:
      | Field Name   | serialized_decimal |
      | Storage Type | Serialized field   |
      | Type         | Decimal            |
    And I click "Continue"
    And I fill form with:
      | Label                | Serialized Decimal |
      | Add To Grid Settings | Yes and display    |
      | Precision            | 29                 |
      | Scale                | 20                 |
    And I save and close form
    Then I should see "Field saved" flash message

  Scenario: Update schema
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario Outline: Check that Percent and Float fields accepts value with big Scale and its cutting in case of big precision
    When I go to System/User Management/Users
    And click Edit admin in grid
    And I fill form with:
      | <Field Name> | <Input Value> |
    And I save form
    Then I should see "User saved" flash message
    And <Field Name> field should has <Expected Input Value> value
    When I go to System/User Management/Users
    Then I should see following grid:
      | Username | <Field Name>          |
      | admin    | <Expected View Value> |
    When I click View admin in grid
    Then I should see User with:
      | <Field Name> | <Expected View Value> |

    Examples:
      | Field Name              | Input Value                      | Expected Input Value             | Expected View Value              |
      | Column Percent          | -0.12345678910111213             | -0.12345678910111                | -0.12345678910111%               |
      | Serialized Percent      | -0.12345678910111213             | -0.12345678910111                | -0.12345678910111%               |
      | Column Float            | -1234.1234567891                 | -1,234.1234567891                | -1,234.1234567891                |
      | Serialized Float        | -0.12345678910111213141516171819 | -0.12345678910111213141516171819 | -0.12345678910111213141516171819 |

  Scenario Outline: Check that Decimal fields accepts value with big Scale and not cut it
    When I go to System/User Management/Users
    And click Edit admin in grid
    And I fill form with:
      | <Field Name> | <Wrong Value> |
    And I save form
    Then I should see validation errors:
      | <Field Name> | <Error message> |
    When I fill form with:
      | <Field Name> | <Correct Value> |
    And I save form
    Then I should see "User saved" flash message
    And <Field Name> field should has <Expected Value> value
    When I go to System/User Management/Users
    Then I should see following grid:
      | Username | <Field Name>     |
      | admin    | <Expected Value> |
    When I click View admin in grid
    Then I should see User with:
      | <Field Name> | <Expected Value> |

    Examples:
      | Field Name              | Wrong Value                 | Error message                                                          | Correct Value                 | Expected Value                  |
      | Typical Column Decimal  | 1234.123456789112345        | This value should be decimal with valid precision (10) and scale (7).  | -117.1234567                  | -117.1234567                    |
      | Specific Column Decimal | 2.1234567891011121314151617 | This value should be decimal with valid precision (29) and scale (20). | 23.999956                     | 23.999956                       |
      | Specific Column Decimal | 2.1234567891011121314151617 | This value should be decimal with valid precision (29) and scale (20). | -1234567.12345678910111213141 | -1,234,567.12345678910111213141 |
      | Serialized Decimal      | 2.1234567891011121314151617 | This value should be decimal with valid precision (29) and scale (20). | -1127.1234567891011121314     | -1,127.1234567891011121314      |

  Scenario Outline: Check that fields correctly accepts value with inline edit
    When I go to System/User Management/Users
    And I edit <Field Name> as "<Input Value>"
#    Remove page reloading after BAP-20396 will be fixed
    When I reload the page
    Then I should see that <Field Name> in 1 row is equal to "<Expected Grid Cell Value>"
    When I start inline editing on "<Field Name>" field I should see "<Expected Inline Input Value>" value

    Examples:
      | Field Name              | Input Value                   | Expected Grid Cell Value        | Expected Inline Input Value   |
      | Column Percent          | -4321.12345678910111          | -4,321.1234567891%              | -4321.1234567891              |
      | Specific Column Decimal | 23.999956                     | 23.999956                       | 23.99995600000000000000       |
      | Specific Column Decimal | -7654321.12345678910111213141 | -7,654,321.12345678910111213141 | -7654321.12345678910111213141 |
      | Column Float            | -4321.1234567891              | -4,321.1234567891               | -4321.1234567891              |

  Scenario: Check that Typical Column Decimal field correctly accepts value with inline edit
    When I go to System/User Management/Users
    And I edit Typical Column Decimal as "1234.123456789112345" without saving
    And I click "Save changes"
    Then I should see "This value should be decimal with valid precision (10) and scale (7)." error message
    When I edit Typical Column Decimal as "-157.1234567"
    Then I should see that Typical Column Decimal in 1 row is equal to "-157.1234567"
    When I start inline editing on "Typical Column Decimal" field I should see "-157.1234567" value

  Scenario Outline: Check that fields filter correctly accepts value
    When I filter <Field Name> as equals "<Wrong Value>"
    Then there is no records in grid
    And I should see filter hints in grid:
      | <Field Name>: equals <Wrong Value Render> |
    And I should see filter <Field Name> field value is equal to "<Wrong Value Render>"
    When I filter <Field Name> as equals "<Correct Value>"
    Then there is one record in grid
    And I should see filter hints in grid:
      | <Field Name>: equals <Correct Value Render> |
    And I should see filter <Field Name> field value is equal to "<Correct Value Render>"

  Examples:
    | Field Name              | Wrong Value                     | Wrong Value Render                | Correct Value                 | Correct Value Render            |
    | Column Percent          | 4343.12345678999999900001234    | 4,343.12345678999999900001234%    | -4321.1234567891              | -4,321.1234567891%              |
    | Column Float            | 4343.12345678999999900001234    | 4,343.12345678999999900001234     | -4321.1234567891              | -4,321.1234567891               |
    | Typical Column Decimal  | 1571234.12345678999999900001234 | 1,571,234.12345678999999900001234 | -157.1234567                  | -157.1234567                    |
    | Specific Column Decimal | 23.999956                       | 23.999956                         | -7654321.12345678910111213141 | -7,654,321.12345678910111213141 |
