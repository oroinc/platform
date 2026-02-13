@regression
@ticket-BB-16231

Feature: Entity config description edit without limitation
  In order to be able to remove, change or set the entity config description without limitation

  Scenario: Check the 550 chars-long description
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    When I click Edit User in grid
    And I fill form with:
      | Description | Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque varius eu nulla ac ultrices. Phasellus sollicitudin dolor erat, nec auctor metus tempor sit amet. Aliquam accumsan lacinia efficitur. In pulvinar nisl erat, at interdum felis ornare ac. Nullam consectetur mi ut justo iaculis cursus. Fusce luctus, nulla vitae ultrices posuere, orci lectus fermentum ex, at eleifend mi dolor id nibh. Maecenas efficitur ante vel leo scelerisque cursus. Praesent malesuada, ligula sit amet dapibus ultrices, ligula turpis luctus est, at sollicitudin dui. |
    And I save form
    Then I should see "Entity saved" flash message
    And I save and close form
    And I should see "Description Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque varius eu nulla ac ultrices. Phasellus sollicitudin dolor erat, nec auctor metus tempor sit amet. Aliquam accumsan lacinia efficitur. In pulvinar nisl erat, at interdum felis ornare ac. Nullam consectetur mi ut justo iaculis cursus. Fusce luctus, nulla vitae ultrices posuere, orci lectus fermentum ex, at eleifend mi dolor id nibh. Maecenas efficitur ante vel leo scelerisque cursus. Praesent malesuada, ligula sit amet dapibus ultrices, ligula turpis luctus est, at sollicitudin dui."

  Scenario: Check removing the description
    Then I click "Edit"
    And I fill form with:
      | Description | |
    And I save form
    Then I should see "Entity saved" flash message
    And Description field is empty
    And I save and close form
    And should see "Description N/A"
