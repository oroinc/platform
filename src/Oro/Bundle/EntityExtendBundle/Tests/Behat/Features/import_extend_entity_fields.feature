@regression
@ticket-BAP-16397
@ticket-BB-14555
# Unskip after BAP-17458
@skip
Feature: Import extend entity fields
  In order to effectively manage extend fields for entities
  As an Administrator
  I need to be able to import extend fields

  Scenario: Data Template for extend fields for User entity
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    When I download "user" extend entity Data Template file
    Then I see fieldName column
    And I see is_serialized column
    And I see type column
    And I see entity.label column
    And I see entity.description column
    And I see entity.contact_information column
    And I see form.is_enabled column
    And I see extend.length column
    And I see importexport.header column
    And I see importexport.order column
    And I see importexport.identity column
    And I see importexport.excluded column
    And I see attachment.mimetypes column
    And I see email.available_in_template column
    And I see datagrid.is_visible column
    And I see datagrid.show_filter column
    And I see datagrid.order column
    And I see view.is_displayable column
    And I see view.priority column
    And I see search.searchable column
    And I see search.title_field column
    And I see dataaudit.auditable column
    And I see extend.precision column
    And I see extend.scale column
    And I see attachment.maxsize column
    And I see attachment.width column
    And I see attachment.height column
    And I see enum.enum_options.0.label column
    And I see enum.enum_options.0.is_default column
    And I see enum.enum_options.1.label column
    And I see enum.enum_options.1.is_default column
    And I see enum.enum_options.2.label column
    And I see enum.enum_options.2.is_default column

  Scenario: Import BigInt Extend Field as "Table Column"
    Given I fill template with data:
      | fieldName         | type   | entity.label    | datagrid.show_filter | datagrid.is_visible |
      | bigIntTableColumn | bigint | FieldText Label | no                   | 0                   |
    When I import file
    And Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text
    When I reload the page
    Then I should see bigIntTableColumn in grid with following data:
      | Storage Type | Table column |
    And I should see "Update schema"
    When I click update schema
    Then I should see Schema updated flash message

  Scenario: Change column which does not require schema update for BigInt Extend Field should not cause Schema Update
    Given I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    And I fill template with data:
      | fieldName         | type   | entity.label    | form.is_enabled | datagrid.is_visible |
      | bigIntTableColumn | bigint | FieldText Label | yes             | 0                   |
    When I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 1, replaced: 0" text
    And I reload the page
    And I should not see "Update schema"

  Scenario: Change column which requires schema update for BigInt Extend Field should cause Schema Update
    When I fill template with data:
      | fieldName         | type   | entity.label    | form.is_enabled | datagrid.is_visible |
      | bigIntTableColumn | bigint | FieldText Label | yes             | 1                   |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 1, replaced: 0" text
    When I reload the page
    Then I should see "Update schema"
    When I click update schema
    Then I should see Schema updated flash message

  # Replace with creation of serialized field with import after fix
  Scenario: Create BigInt Extend Field as "Serialized field" when field which requires schema update is changed
    Given I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    And I click "Create Field"
    And I fill form with:
      | Field name   | bigIntSerialized |
      | Storage type | Serialized field |
      | Type         | BigInt           |
    When I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message
    And I should not see "Update schema"
    And I should see bigIntSerialized in grid with following data:
      | Storage type | Serialized field |

  Scenario: Change column which requires schema update for serialized field should NOT cause Schema Update
    Given I fill template with data:
      | fieldName        | type   | entity.label    | datagrid.show_filter | datagrid.is_visible |
      | bigIntSerialized | bigint | FieldText Label | no                   | 0                   |
    When I import file
    And Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 1, replaced: 0" text
    And I reload the page
    Then I should not see "Update schema"

  Scenario: It should be impossible to import columns with similar or invalid names or properties
    When I fill template with data:
      | fieldName            | type   | entity.label    | datagrid.show_filter | datagrid.is_visible |
      | correct_field_name   | bigint | FieldText Label | no                   | 0                   |
      | correctFieldName     | bigint | FieldText Label | no                   | 0                   |
      | inc@rrect_field_name | bigint | FieldText Label | no                   | 0                   |
      | incorrect_field      | qwerty | FieldText Label | no                   | 0                   |
      | UNION                | bigint | FieldText Label | no                   | 0                   |
      |                      | bigint | FieldText Label | no                   | 0                   |
      | correct_field_name_2 |        | FieldText Label | no                   | 0                   |
    And I import file
    Then Email should contains the following "Errors: 6 processed: 7, read: 7, added: 1, updated: 0, replaced: 0" text
    When I reload the page
    Then I should see correct_field_name in grid
    And I should not see "correctFieldName"
    And I should not see "inc@rrect_field_name"
    And I should not see "incorrect_field"
    And I should not see "UNION"
    And I should not see "correct_field_name_2"
    And I should see "Update schema"
    When I click update schema
    Then I should see Schema updated flash message

  Scenario: It should be impossible to import attributes with correct names
    Given I filter Name as is equal to "User"
    And click View User in grid
    And I fill template with data:
      | fieldName            | type   | entity.label  | datagrid.show_filter | datagrid.is_visible |
      | Tv                   | string | label value 2 | no                   | 0                   |
      | Text_underscore_text | string | label value 3 | no                   | 0                   |
      | Myand4               | string | label value 4 | no                   | 0                   |
      | koko                 | string | label value 5 | no                   | 0                   |
      | LOREM                | string | label value 6 | no                   | 0                   |
      | SunSet               | string | label value 7 | no                   | 0                   |
    When I import file
    Then Email should contains the following "Errors: 0 processed: 6, read: 6, added: 6, updated: 0, replaced: 0" text
    When I reload the page
    And I should see Tv in grid
    And I should see "label value 2" in grid
    And I should see Text_underscore_text in grid
    And I should see "label value 3" in grid
    And I should see Myand4 in grid
    And I should see "label value 4" in grid
    And I should see koko in grid
    And I should see "label value 5" in grid
    And I should see LOREM in grid
    And I should see "label value 6" in grid
    And I should see SunSet in grid
    And I should see "label value 7" in grid
    And I should see "Update schema"
    When I click update schema
    Then I should see Schema updated flash message

  Scenario: It should be impossible to import attributes with incorrect names
    Given I filter Name as is equal to "User"
    And click View User in grid
    And I fill template with data:
      | fieldName                                          | type   | entity.label   | datagrid.show_filter | datagrid.is_visible |
      | null                                               | string | label value 8  | no                   | 0                   |
      | LoremIpsumLoremIpsumLoremIpsumLoremIpsumLoremIpsum | string | label value 9  | no                   | 0                   |
      | лорем_иъий                                         | string | label value 10 | no                   | 0                   |
      | A                                                  | string | label value 11 | no                   | 0                   |
      | U+004C                                             | string | label value 12 | no                   | 0                   |
      | &^$                                                | string | label value 13 | no                   | 0                   |
      | 4&a                                                | string | label value 14 | no                   | 0                   |
      | &A                                                 | string | label value 15 | no                   | 0                   |
      | #^*()                                              | string | label value 16 | no                   | 0                   |
      | _loremipsum                                        | string | label value 17 | no                   | 0                   |
    When I import file
    Then Email should contains the following "Errors: 10 processed: 0, read: 10, added: 0, updated: 0, replaced: 0" text
    When I reload the page
    And I should not see "null"
    # will be fixed in BB-14718
    And I should see "LoremIpsumLoremIpsumLoremIpsumLoremIpsumLoremIpsum"
    And I should not see "лорем_иъий"
    # will be fixed in BB-14718
    And I should see "A"
    And I should not see "correctFieldName"
    And I should not see "inc@rrect_field_name"
    And I should not see "incorrect_field"
    And I should not see "UNION"
    And I should not see "correct_field_name_2"
    And I should not see "U+004C"
    And I should not see "&^$"
    And I should not see "4&a"
    And I should not see "&A"
    And I should not see "#^*()"
    And I should not see "_loremipsum"
    And I should see "Update schema"
    When I click update schema
    Then I should see Schema updated flash message

  Scenario: It should be impossible to updated columns with similar names
    Given I filter Name as is equal to "User"
    And click View User in grid
    And I fill template with data:
      | fieldName          | type   | entity.label            | datagrid.show_filter | datagrid.is_visible |
      | correct_field_name | bigint | FieldText Label         | no                   | 0                   |
      | correctFieldName   | bigint | FieldText Label updated | no                   | 0                   |
    When I import file
    Then Email should contains the following "Errors: 1 processed: 1, read: 2, added: 0, updated: 1, replaced: 0" text
    When I reload the page
    Then I should see correct_field_name in grid
    And I should see "FieldText Label" in grid
    And I should not see "correctFieldName"
    And I should not see "FieldText Label updated"
    And I should not see "Update schema"

  Scenario: It should be possible to updated columns with the same name
    Given I fill template with data:
      | fieldName          | type   | entity.label            | datagrid.show_filter | datagrid.is_visible |
      | correct_field_name | bigint | FieldText Label updated | no                   | 0                   |
      | correctFieldName   | bigint | FieldText Label         | no                   | 0                   |
    When I import file
    Then Email should contains the following "Errors: 1 processed: 1, read: 2, added: 0, updated: 1, replaced: 0" text
    When I reload the page
    Then I should see correct_field_name in grid
    And I should see "FieldText Label updated" in grid
    And I should not see "correctFieldName"
    And I should not see "Update schema"

  Scenario: It should be impossible to import columns with invalid field name
    Given I fill template with data:
      | fieldName                 | type   | entity.label       | datagrid.show_filter | datagrid.is_visible |
      | <script>alert(1)</script> | string | string field Label | no                   | 0                   |
    When I try import file
    Then I should not see "Import File Field Validation" element with text "The mime type of the file is invalid" inside "Import File Form" element
    When I reload the page
    Then I should not see "Update schema"
