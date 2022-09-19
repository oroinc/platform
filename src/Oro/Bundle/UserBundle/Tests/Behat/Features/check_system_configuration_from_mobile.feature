@ticket-BAP-21510

Feature: Check system configuration from mobile
  As a administrator
  I need to check the system configuration from a mobile device

  Scenario: Feature Background
    Given sessions active:
      | admin_desktop | first_session  |
      | admin_mobile  | mobile_session |

  Scenario: Check visibility for the mobile version
    Given I proceed as the admin_mobile
    And I login as administrator
    When I click "Mobile Menu Toggler"
    Then I should not see "System"
    When I go to "/admin/config/user/profile"
    Then I should see "System configuration is not available in mobile version. Please open the page on the desktop."

  Scenario: Check visibility for the desktop version
    Given I proceed as the admin_desktop
    And I login as administrator
    When I click My Configuration in user menu
    Then I should not see "System configuration is not available in mobile version. Please open the page on the desktop."
    And I should see "Location options"
    And I should see "Primary Location"
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    And I should see "Sidebar settings"
