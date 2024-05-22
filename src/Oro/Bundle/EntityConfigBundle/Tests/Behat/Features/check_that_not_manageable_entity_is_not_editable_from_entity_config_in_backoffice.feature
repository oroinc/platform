Feature: Check that not manageable entity is not editable from entity config in backoffice
  As an Administrator
  I want to be sure that that not have possibility to edit not manageable entity and it`s fields from entity config in backoffice
  So I check that entity view page, entity config and field config grid has no action buttons to modify entity and it`s fields

  Scenario: Check entity management datagrid
    Given I login as administrator
    When I go to System/Entities/Entity Management
    And filter Name as is equal to "File"
    Then I should see following actions for File in grid:
      | View |
    And I should not see following actions for File in grid:
      | Edit  |
      | Delete|

  Scenario: Check entity view page and entity fields datagrid
    When I click view File in grid
    Then I should not see "Import file"
    And I should not see "Edit"
    And I should not see "Manage Unique keys"
    And I should not see "Create field"
    When I scroll to bottom
    Then I should not see following actions for id in grid:
      | View   |
      | Edit   |
      | Delete |
    And I should not see following actions for filename in grid:
      | View   |
      | Edit   |
      | Delete |
    And I should not see following actions for mimeType in grid:
      | View   |
      | Edit   |
      | Delete |
    And I should not see following actions for originalFilename in grid:
      | View   |
      | Edit   |
      | Delete |
    And I should not see following actions for fileSize in grid:
      | View   |
      | Edit   |
      | Delete |
