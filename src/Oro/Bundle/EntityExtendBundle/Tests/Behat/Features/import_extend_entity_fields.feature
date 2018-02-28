@regression
@ticket-BAP-16397
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
    When I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    And I fill template with data:
      | fieldName         | type   | entity.label    | form.is_enabled | datagrid.is_visible |
      | bigIntTableColumn | bigint | FieldText Label | yes             | 0                   |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text
    When I reload the page
    Then I should not see "Update schema"

  Scenario: Change column which requires schema update for BigInt Extend Field should cause Schema Update
    When I fill template with data:
      | fieldName         | type   | entity.label    | form.is_enabled | datagrid.is_visible |
      | bigIntTableColumn | bigint | FieldText Label | yes             | 1                   |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text
    When I reload the page
    Then I should see "Update schema"
    When I click update schema
    Then I should see Schema updated flash message

  # Replace with creation of serialized field with import after fix
  Scenario: Create BigInt Extend Field as "Serialized field" when field which requires schema update is changed
    When I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    When I click "Create Field"
    Then I fill form with:
      | Field name   | bigIntSerialized |
      | Storage type | Serialized field |
      | Type         | BigInt           |
    When I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message
    And I should not see "Update schema"
    Then I should see bigIntSerialized in grid with following data:
      | Storage type | Serialized field |

  Scenario: Change column which requires schema update for serialized field should NOT cause Schema Update
    Given I fill template with data:
      | fieldName        | type   | entity.label    | datagrid.show_filter | datagrid.is_visible |
      | bigIntSerialized | bigint | FieldText Label | no                   | 0                   |
    When I import file
    And Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text
    When I reload the page
    Then I should not see "Update schema"
