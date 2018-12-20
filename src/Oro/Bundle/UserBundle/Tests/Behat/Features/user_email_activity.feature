@ticket-BAP-10957
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
# todo: Uncomment by resolve BAP-11089
#  And add email attachments "email-attachment.jpg, email-attachment2.jpg, email-attachment3.jpg"
#  And delete email-attachment2.jpg attachment
  When click "Send"
  Then I should see "The email was sent" flash message
  And should see "Work for you" email in activity list

Scenario Outline: View email in activity list
  Given I go to System/User Management/Users
  And click view <name> in grid
  When I collapse "Work for you" in activity list
  Then I should see "We have new role for you" in email body
  And I should see <name> in Contexts
  Examples:
    | name   |
    | audrey |
    | brad   |

Scenario: Response email
  Given I go to System/User Management/Users
  And click view Audrey in grid
  And I click "Reply" on "Work for you" in activity list
  When I click "Send"
  Then email "Work for you" should have thread icon
  When I collapse "Work for you" in activity list
  Then email thread "Work for you" should have two emails

# todo: Uncomment by resolve BAP-11089
#Scenario: View attachments
#  Given I go to System/User Management/Users
#  And click view Audrey in grid
#  When I click on "Work for you" in activity list
#  And I should see 2 attachments
#  When I click on 1 attachment
#  Then I should see view of 1 attachment
#  When I click next attachment
#  Then I should see view of 2 attachment

Scenario: Forward email
  Given shouldn't see "Fwd: Re: Work for you" email in activity list
  When I click "Forward" on "Work for you" in activity list
  And I fill in "To" with "Bruce"
  And click "Send"
  Then should see "Fwd: Re: Work for you" email in activity list

Scenario: Delete contexts
  When I collapse "Fwd: Re: Work for you" in activity list
  And delete "John Doe" context from collapsed email
  Then I should see "The context has been removed" flash message
  When I delete "Audrey Hepburn" context from collapsed email
  Then I should see "The context has been removed" flash message
  And shouldn't see "Fwd: Re: Work for you" email in activity list
  When I go to System/User Management/Users
  And click view Brad in grid
  And shouldn't see "Fwd: Re: Work for you" email in activity list

Scenario: Add contexts
  Given I click My emails in user menu
  And I click View Work for you in grid
  And follow "Add Context"
  And select User in activity context selector
  When click on Audrey in grid
  Then I should see "The context has been added" flash message
  When I go to System/User Management/Users
  And click view Audrey in grid
  Then I should see "Fwd: Re: Work for you" email in activity list
