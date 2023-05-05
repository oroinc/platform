@regression
@ticket-BB-15166
@behat-test-env
Feature: Mime types upload settings admin configuration
  In order to manage upload settings
  As an administrator
  I should be able to set mime types only which were allowed in global configuration

  Scenario: Update mime type section in upload settings
    Given I login as administrator
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Upload Settings" on configuration sidebar
    And uncheck "Use default" for "File MIME types" field
    Then I should see "File MIME Types" with options:
      | Value                                                                     |
      | text/csv                                                                  |
      | text/plain                                                                |
      | application/msword                                                        |
      | application/vnd.openxmlformats-officedocument.wordprocessingml.document   |
      | application/vnd.ms-excel                                                  |
      | application/vnd.openxmlformats-officedocument.spreadsheetml.sheet         |
      | application/vnd.ms-powerpoint                                             |
      | application/vnd.openxmlformats-officedocument.presentationml.presentation |
      | application/pdf                                                           |
      | application/zip                                                           |
      | image/gif                                                                 |
      | image/jpeg                                                                |
      | image/png                                                                 |
      | image/svg                                                                 |
      | image/svg+xml                                                             |
    When I unselect "application/vnd.ms-excel" option from "File MIME Types"
    And uncheck "Use default" for "Image MIME types" field
    Then I should see "Image MIME Types" with options:
      | Value         |
      | image/gif     |
      | image/jpeg    |
      | image/png     |
      | image/svg     |
      | image/svg+xml |
    When I unselect "image/png" option from "Image MIME Types"
    And I submit form
    Then I should see "Configuration saved" flash message
