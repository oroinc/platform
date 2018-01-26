@regression

Feature: Resize Sidebar
  In order to see whole content of sidebar
  As a Site Administrator
  I want to be able to resize sidebar

#  320px is minimum default value
#  600px is maximum default value

  Scenario: Resize System Configuration Sidebar
    Given I login as administrator
    And I go to System/Configuration
    And I check element "Sidebar" has width "320"
    And I resize Sidebar Drag Handler by vector [300,0]
    And I check element "Sidebar" has width "600"
    And I reload the page
    And I check element "Sidebar" has width "600"
    And I resize Sidebar Drag Handler by vector [-300,0]
    Then I check element "Sidebar" has width "320"
    When I click on "Close Sidebar Trigger"
    Then I should see a "Closed Sidebar" element
    And I should see a "Not Resizable Sidebar" element
    When I reload the page
    Then I should see a "Closed Sidebar" element
    When I click on "Open Sidebar Trigger"
    Then should not see a "Closed Sidebar" element
    And I check element "Sidebar" has width "320"

  Scenario: Resize Products Sidebar
    Given I go to Products/Products
    And I check element "Sidebar" has width "320"
    And I resize Sidebar Drag Handler by vector [300,0]
    And I check element "Sidebar" has width "600"
    And I reload the page
    And I check element "Sidebar" has width "600"
    And I resize Sidebar Drag Handler by vector [-300,0]
    And I check element "Sidebar" has width "320"

  Scenario: Resize Products / Master Catalog Sidebar
    Given I go to Products/Master Catalog
    And I check element "Sidebar" has width "320"
    And I resize Sidebar Drag Handler by vector [300,0]
    And I check element "Sidebar" has width "600"
    And I reload the page
    And I check element "Sidebar" has width "600"
    And I resize Sidebar Drag Handler by vector [-300,0]
    And I check element "Sidebar" has width "320"
