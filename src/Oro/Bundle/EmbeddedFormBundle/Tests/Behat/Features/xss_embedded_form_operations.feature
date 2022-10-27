@waf-skip
Feature: Xss embedded form operations

    Scenario: Create Embedded form without xss
        Given I login as administrator
        And I go to System/Integrations/Embedded Forms
        And click "Create Embedded Form"
        And fill form with:
            | Title     | Embedded Form 1  |
            | Form Type | Contact Request  |
        When I save and close form
        Then I should see "Form has been saved successfully" flash message

    Scenario: Edit and view embedded form with javascript content
        Given I click "Edit"
        When I fill form with:
            | Title           | <script>alert(2)</script>  |
            | Success Message | <script>alert(3)</script>  |
        And I save and close form
        Then I should see "Form has been saved successfully" flash message
        And I should see embedded form with:
            | Title           | <script>alert(2)</script>  |
            | Success Message | <script>alert(3)</script>  |
        And I should not see alert

    Scenario: View embedded form with javascript content in grid
        Given I go to System/Integrations/Embedded Forms
        And I should not see alert
