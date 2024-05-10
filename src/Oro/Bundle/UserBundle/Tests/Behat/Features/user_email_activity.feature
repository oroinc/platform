@regression
@ticket-BAP-10957
@ticket-BAP-22197
@automatically-ticket-tagged

Feature: User email activity
  In order to have ability send email to user
  As OroCRM sales rep
  I need to have email activity functionality

Scenario: Send email
  Given the following users:
    | firstName | lastName | email              | username | authStatus         |
    | Audrey    | Hepburn  | audrey@hepburn.com | audrey   | @admin->authStatus |
    | Brad      | Pitt     | brad@pitt.com      | brad     | @admin->authStatus |
    | Bruce     | Willis   | bruce@willis.com   | bruce    | @admin->authStatus |
  And I login as administrator
  And I go to System/User Management/Users
  And click view Audrey in grid
  And follow "More actions"
  And click "Send email"
  And fill form with:
    | Subject  | Work for you                                                    |
    | To       | [Audrey, Pitt]                                                  |
    | Body     | Hi Man! We have new role for you, you will be happy, I promise! |
    | Contexts | [Audrey, Pitt]                                                  |
  When click "Send"
  Then I should see "The email was sent" flash message

Scenario: Check email activities
  Given I click My Emails in user menu
  When I click view "Work for you" in grid
  Then I should see "Audrey Hepburn" in the "Email Page Contexts" element
  And I should see "Brad Pitt" in the "Email Page Contexts" element

Scenario: Check aggregated activities for threaded email
  When I click "Reply"
  And fill form with:
    | Contexts | [Audrey, Bruce] |
  And I click "Send"
  Then I should see "The email was sent" flash message
  And I should see "Audrey Hepburn" in the "Email Page Contexts" element
  And I should see "Brad Pitt" in the "Email Page Contexts" element
  And I should see "Bruce Willis" in the "Email Page Contexts" element

Scenario: Remove activity that exists in several emails in the thread
  When I press "Email View Delete Audrey Hepburn Context"
  Then I should see "The context has been removed" flash message
  And I should not see "Audrey Hepburn" in the "Email Page Contexts" element
  And I should see "Brad Pitt" in the "Email Page Contexts" element
  And I should see "Bruce Willis" in the "Email Page Contexts" element
