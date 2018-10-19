@fixture-OroFilterBundle:is_any_of_and_is_not_any_of_number_filters.yml
Feature: "is any of" and "is not any of" number filters
    In order to filter numbers by list
    As administrator
    I need to be able to use "is any of" and "is not any of" number filters

    Scenario: Add float property for User
        Given I login as administrator
        And I go to System/Entities/Entity Management
        And filter Name as is equal to "User"
        And click View User in grid
        And I click on "Create Field"
        And I fill form with:
            | Field name    | float_field |
            | Type          | Float       |
        And I click "Continue"
        And I save and close form
        Then click update schema

    Scenario: "is any of" condition
        Given I go to Reports & Segments/ Manage Segments
        And I click Edit Some segment name in grid
        And I add the following filters:
            | Field Condition | Id | is any of | 2,3 |
        And I save and close form
        Then I should see following grid:
            | ID |
            | 2  |
            | 3  |

    Scenario: "is not any of" condition
        Given I go to Reports & Segments/ Manage Segments
        And I click Edit Some segment name in grid
        And I click on "Remove condition"
        And I add the following filters:
            | Field Condition | Id | is not any of | 2,3 |
        And I save and close form
        Then I should see following grid:
            | ID |
            | 1  |

    Scenario: "is any of" and "is not any of" conditions applicable only for integers
        Given I go to Reports & Segments/ Manage Segments
        And I click Edit Some segment name in grid
        And I click on "Remove condition"
        And I add "Field Condition" filter
        And I choose "float_field" filter column
        And I click on "Filter Condition Dropdown"
        Then I should not see "is any of"
        And I should not see "is not any of"
