@ticket-BAP-13745
@automatically-ticket-tagged
Feature: Business Unit crud
  In order to keep organize my business structure
  As administrator
  I need to CRUD business units

  Scenario: Required fields Business Unit create form
    Given I login as administrator
    And I go to System/ User Management/ Business Units
    And click "Create Business Unit"
    When I save and close form
    Then I should see validation errors:
      | Name | This value should not be blank. |

  Scenario: Create Buisness Unit
    Given I fill form with:
      | Name        | Acme Business Unit |
      | Phone       | 45-96-23           |
      | Website     | http://example.com |
      | Email       | acme@example.com   |
      | Fax         | 45-96-24           |
      | Description | Important guy      |
    And I check first one record in 0 column
    When I save and close form
    Then I should see "Business unit saved" flash message
    And I should see Business Unit with:
      | Name         | Acme Business Unit |
      | Organization | ORO                |
      | Phone        | 45-96-23           |
      | Website      | http://example.com |
      | Email        | acme@example.com   |
      | Fax          | 45-96-24           |
      | Description  | Important guy      |

  Scenario: Checking user business units
    Given I go to System/ User Management/ Users
    When I click View John in grid
    Then I should see User with:
      | Username       | admin                   |
      | Business Units | Main Acme Business Unit |

  Scenario: Edit business unit
    Given I go to System/ User Management/ Business Units
    And click edit Acme Business Unit in grid
    And I fill form with:
      | Name        | Demo Business Unit |
      | Phone       | 68-32-23           |
      | Website     | http://example.org |
      | Email       | demo@example.org   |
      | Fax         | 68-32-24           |
      | Description | Good unit          |
    When I save and close form
    Then I should see "Business unit saved" flash message
    And I should see Business Unit with:
      | Name         | Demo Business Unit |
      | Organization | ORO                |
      | Phone        | 68-32-23           |
      | Website      | http://example.org |
      | Email        | demo@example.org   |
      | Fax          | 68-32-24           |
      | Description  | Good unit          |

  Scenario: Checking user business units
    Given I go to System/ User Management/ Users
    When I click View John in grid
    Then I should see User with:
      | Username       | admin                   |
      | Business Units | Main Demo Business Unit |

  Scenario: Delete Business unit
    Given I go to System/ User Management/ Business Units
    And there are two records in grid
    When click delete Demo Business Unit in grid
    And confirm deletion
    Then number of records should be 1
