@regression
@fixture-OroUserBundle:user.yml
Feature: Actions applicability in grid
  ToDo: BAP-16103 Add missing descriptions to the Behat features

  Scenario: Administrator should see all actions except delete for system email templates
    Given I login as administrator
    And I go to System/Emails/Templates
    When I filter Template Name as is equal to "user_change_password"
    Then I should see following actions for user_change_password in grid:
      | Edit |
      | Clone |
    But I should not see following actions for user_change_password in grid:
      | Delete |

  Scenario: Administrator should see all actions for own email templates
    Given I go to System/Emails/Templates
    When I filter Template Name as is equal to "quote_email_link"
    Then I should see following actions for quote_email_link in grid:
      | Edit |
      | Clone |
      | Delete |

  Scenario: Sales Rep should not see actions for system email templates
    Given I login as "charlie" user
    And I go to System/Emails/Templates
    When I filter Template Name as is equal to "user_change_password"
    Then I should not see following actions for user_change_password in grid:
      | Edit |
      | Clone |
      | Delete |

  Scenario: Sales Rep should not see actions for administrator's email templates
    Given I go to System/Emails/Templates
    When I filter Template Name as is equal to "quote_email_link"
    Then I should not see following actions for quote_email_link in grid:
      | Edit |
      | Clone |
      | Delete |

  Scenario: Sales Rep should see actions for created email template
    Given I go to System/Emails/Templates
    And I click "Create Email Template"
    When I fill form with:
      | Template Name | new_template |
    And I save and close form
    And I go to System/Emails/Templates
    And I filter Template Name as is equal to "new_template"
    Then I should see following actions for new_template in grid:
      | Edit |
      | Clone |
      | Delete |
