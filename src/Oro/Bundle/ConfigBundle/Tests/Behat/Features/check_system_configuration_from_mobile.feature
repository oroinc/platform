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
    When I go to "/admin/config/system"
    Then I should see "System configuration is not available in mobile version. Please open the page on the desktop."

  Scenario: Check visibility for the desktop version
    Given I proceed as the admin_desktop
    And I login as administrator
    When I go to System/ Configuration
    Then I should not see "System configuration is not available in mobile version. Please open the page on the desktop."
