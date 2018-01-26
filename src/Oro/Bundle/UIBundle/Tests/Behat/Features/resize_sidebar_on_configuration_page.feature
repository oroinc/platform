@regression

Feature: Resize Sidebar on Configuration Page
  In order to see whole content of sidebar
  As a Site Administrator
  I want to be able to resize sidebar

#  320px is minimum default value
#  600px is maximum default value

  Scenario: Resize System Configuration Sidebar
    Given I login as administrator
    And I go to System/Configuration
    And I check element "Sidebar" has width "320"
    When I resize Sidebar Drag Handler by vector [300,0]
    Then I check element "Sidebar" has width "600"
    When I reload the page
    And I check element "Sidebar" has width "600"
    Then I resize Sidebar Drag Handler by vector [-300,0]
    And I check element "Sidebar" has width "320"
    When I click on "Close Sidebar Trigger"
    Then I should see a "Closed Sidebar" element
    And I should see a "Not Resizable Sidebar" element
    When I reload the page
    Then I should see a "Closed Sidebar" element
    When I click on "Open Sidebar Trigger"
    Then should not see a "Closed Sidebar" element
    And I check element "Sidebar" has width "320"
