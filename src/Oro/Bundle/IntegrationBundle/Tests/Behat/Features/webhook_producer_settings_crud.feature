@regression

Feature: Webhook producer settings CRUD
  In order to manage webhook endpoints
  As an Administrator
  I need to be able to create, view, update and delete webhooks

  Scenario: Admin login
    Given I login as administrator

  Scenario Outline: Enable webhook access for entities
    When I go to System/Entities/Entity Management
    And I filter Name as is equal to "<entity>"
    And I click view <entity> in grid
    And I click "Edit"
    And I fill form with:
      | Webhook accessible | Yes |
    And I save and close form
    Then I should see "Entity saved" flash message

    Examples:
      | entity       |
      | Organization |
      | Localization |

  Scenario: Check webhook creation validation
    When I go to System/Integrations/Webhooks
    And I click "Create Webhook"
    And I save and close form
    Then I should see validation errors:
      | Notification URL | This value should not be blank. |
      | Topic            | This value should not be blank. |
      | Payload Format   | This value should not be blank. |
    And I click "Cancel"

  Scenario Outline: Create webhooks
    When I go to System/Integrations/Webhooks
    And I click "Create Webhook"
    And I fill form with:
      | Notification URL | <notificationUrl>  |
      | Topic            | <topic>            |
      | Payload Format   | Default (JSON:API) |
      | Enabled          | <enabled>          |
    And I save and close form
    Then I should see "Webhook settings saved successfully" flash message

    Examples:
      | notificationUrl              | topic                | enabled |
      | https://example.com/webhook1 | organization.created | true    |
      | https://example.com/webhook2 | localization.updated | false   |
      | https://example.com/webhook3 | organization.deleted | true    |

  Scenario: View Webhook details
    When I go to System/Integrations/Webhooks
    Then I should see following grid containing rows:
      | Notification URL             | Topic                                     | Enabled |
      | https://example.com/webhook1 | Organization created organization.created | Yes     |
      | https://example.com/webhook3 | Organization deleted organization.deleted | Yes     |
      | https://example.com/webhook2 | Localization updated localization.updated | No      |
    And number of records should be 3
    When I click view "https://example.com/webhook1" in grid
    Then I should see Webhook with:
      | Notification URL | https://example.com/webhook1 |
      | Topic            | organization.created         |
      | Verify SSL       | Yes                          |
      | Payload Format   | Default (JSON:API)           |

  Scenario: Create additional webhook
    When I go to System/Integrations/Webhooks
    And I click "Create Webhook"
    And I fill form with:
      | Enabled          | true                         |
      | Notification URL | https://example.com/webhook4 |
      | Secret           | new_secret_key               |
      | Topic            | organization.created         |
      | Payload Format   | Default (JSON:API)           |
    And I save and close form
    Then I should see "Webhook settings saved successfully" flash message

  Scenario: Update Webhook
    When I go to System/Integrations/Webhooks
    And I click edit "https://example.com/webhook1" in grid
    And I fill form with:
      | Notification URL | https://example.com/webhook1-updated |
      | Topic            | localization.updated                 |
      | Enabled          | false                                |
    And I save and close form
    Then I should see "Webhook settings saved successfully" flash message

  Scenario: Filter and sort Webhooks
    When I go to System/Integrations/Webhooks
    Then number of records should be 4

    # Filter by topic
    When I filter Topic as contains "organization"
    Then I should see following grid containing rows:
      | Notification URL             | Topic                                     | Enabled |
      | https://example.com/webhook4 | Organization created organization.created | Yes     |
      | https://example.com/webhook3 | Organization deleted organization.deleted | Yes     |
    And number of records should be 2
    And I should not see "localization"

    # Filter by enabled status
    When I reset "Topic" filter
    And I check "Yes" in "Enabled" filter
    Then I should see following grid containing rows:
      | Notification URL             | Topic                                     | Enabled |
      | https://example.com/webhook4 | Organization created organization.created | Yes     |
      | https://example.com/webhook3 | Organization deleted organization.deleted | Yes     |
    And number of records should be 2

    # Filter by notification URL
    When I reset "Enabled" filter
    And I filter Notification URL as contains "webhook4"
    Then number of records should be 1
    And I should see following grid containing rows:
      | Notification URL             | Topic                                     | Enabled |
      | https://example.com/webhook4 | Organization created organization.created | Yes     |

    # Sort by notification URL
    When I reset "Notification URL" filter
    And I sort grid by "Notification URL"
    Then I should see following grid with exact columns order:
      | Topic                                     | Notification URL                     |
      | Localization updated localization.updated | https://example.com/webhook1-updated |
      | Localization updated localization.updated | https://example.com/webhook2         |
      | Organization deleted organization.deleted | https://example.com/webhook3         |
      | Organization created organization.created | https://example.com/webhook4         |

  Scenario: Delete Webhooks
    When I go to System/Integrations/Webhooks
    Then number of records should be 4

    # Delete single webhook
    When I click delete "https://example.com/webhook2" in grid
    And I confirm deletion
    Then I should see "Webhook Producer Settings deleted" flash message
    And I should not see "https://example.com/webhook2"
    And number of records should be 3

    # Mass delete webhooks
    When I keep in mind number of records in list
    And I check first 2 records in grid
    And I click "Delete" link from mass action dropdown
    And I confirm deletion
    Then I should see "2 entities have been deleted successfully" flash message
    And the number of records decreased by 2
    And number of records should be 1
