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
