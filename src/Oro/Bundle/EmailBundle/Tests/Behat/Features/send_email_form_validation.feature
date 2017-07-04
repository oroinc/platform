Feature: Send email form
  Check form validation
  As Administrator
  I need to be able to send email

  Scenario: Send form without email
    Given I login as administrator
    And I click My Emails in user menu
    And there is no records in grid
    When I follow "Compose"
    And fill "Email Form" with:
      | Body    | This is very simple test mail |
    And press "Send"
    Then I should see "Email Form" validation errors:
      | ToField | This value contains not valid email address. |
      | Subject | This value should not be blank.              |
    And I close ui dialog
