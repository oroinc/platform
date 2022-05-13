@fixture-OroEmailBundle:public-private-emails.yml
@ticket-BAP-21219

Feature: Email ACL in reports
  In order to manage Email feature
  As an Administrator
  I should be able to manipulate visibility of emails in custom reports

  Scenario: Scenario background
    Given I login as administrator

  Scenario: Report with full ACL access
    Given I go to Reports & Segments/ Manage Custom Reports
    And I click "Create Report"
    And I fill "Report Form" with:
      | Name        | Emails report |
      | Entity      | User Emails   |
      | Report Type | Table         |
    And I add the following columns:
      | Private        |
      | Subject        |
      | User->Username |
    When I save and close form
    Then I should see "Report saved" flash message
    And number of records should be 2
    And I should see following grid:
      | Private | Subject       | Username         |
      | No      | Public email  | charlie.sheen123 |
      | Yes     | Private email | admin            |

  Scenario: Report without access to private emails
    Given I go to System/ User Management/ Roles
    And I click edit "Administrator" in grid
    And select following permissions:
      | User Emails | View private:None |
    And save and close form
    When I go to Reports & Segments/ User Emails/ Emails report
    Then number of records should be 1
    And I should see following grid:
      | Private | Subject       | Username         |
      | No      | Public email  | charlie.sheen123 |

  Scenario: Report without access to public emails
    Given I go to System/ User Management/ Roles
    And I click edit "Administrator" in grid
    And select following permissions:
      | User Emails | View private:Global | View:None |
    And save and close form
    When I go to Reports & Segments/ User Emails/ Emails report
    Then number of records should be 1
    And I should see following grid:
      | Private | Subject       | Username         |
      | Yes     | Private email | admin            |

  Scenario: Report without access to emails
    Given I go to System/ User Management/ Roles
    And I click edit "Administrator" in grid
    And select following permissions:
      | User Emails | View private:None | View:None |
    And save and close form
    When I go to Reports & Segments/ User Emails/ Emails report
    Then number of records should be 0
