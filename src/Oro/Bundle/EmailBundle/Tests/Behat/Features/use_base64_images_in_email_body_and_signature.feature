@regression
Feature: Use base64 images in email body and signature
  As a user
  I want to see base64 images used in email body or signature

  Scenario: Set user's email signature
    Given I login as administrator
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Display Settings" on configuration sidebar
    When uncheck "Use default" for "Enable WYSIWYG editor" field
    And I fill form with:
      | Enable WYSIWYG editor | true |
    When I click "Save settings"
    Then I should see "Configuration saved" flash message
    And I click My Configuration in user menu
    And I follow "System Configuration/General Setup/Email Configuration" on configuration sidebar
    And uncheck "Use Organization" for "Signature Content" field
    And I click "WYSIWYG Source code button"
    And I fill in "WYSIWYG textarea" with "<img src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAMAAAADCAYAAABWKLW/AAAAEklEQVR42mP8z8AARBDAiJMDAIzoBf5Dp2P2AAAAAElFTkSuQmCC' alt='Red dot'>"
    And I click "Ok"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check images after sending an email
    Given I click My Emails in user menu
    And I click "Compose"
    Then I should see "Red dot image" element inside "WYSIWYG editor" iframe
    And I fill "Email Form" with:
      | To      | John Doe   |
      | Subject | Test email |
    And I click "WYSIWYG Source code button"
    Then the "WYSIWYG textarea" field element should contain:
      """
      <!DOCTYPE html>
      <html>
      <head>
      </head>
      <body>
      <p><br /><br /></p>
      <p><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAMAAAADCAYAAABWKLW/AAAAEklEQVR42mP8z8AARBDAiJMDAIzoBf5Dp2P2AAAAAElFTkSuQmCC" alt="Red dot" /></p>
      </body>
      </html>
      """
    And I fill in "WYSIWYG textarea" with:
      """
      <!DOCTYPE html>
      <html>
      <head>
      </head>
      <body>
      <p><br />
      <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAMAAAADCAYAAABWKLW/AAAAEklEQVR42mNkYPj/nwEKGHFyAIbuBf4sIeHsAAAAAElFTkSuQmCC" alt="Blue dot">
      <br /></p>
      <p><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAMAAAADCAYAAABWKLW/AAAAEklEQVR42mP8z8AARBDAiJMDAIzoBf5Dp2P2AAAAAElFTkSuQmCC" alt="Red dot" /></p>
      </body>
      </html>
      """
    And I click "Ok"
    And I click "Send"
    Then I should see "The email was sent" flash message
    And I click on Test email in grid
    Then I should see "Red dot image" element inside "Email body" iframe
    And I should see "Blue dot image" element inside "Email body" iframe

  Scenario: Check images after replying to an email
    Given I click "Reply"
    Then I should see "Red dot image" element inside "WYSIWYG editor" iframe
    And I should see "Blue dot image" element inside "WYSIWYG editor" iframe
    And I click "Send"
    And I click My Emails in user menu
    Then I click on Test email in grid
    Then I should see "Red dot image" element inside "Email body" iframe
    And I should see "Blue dot image" element inside "Email body" iframe
