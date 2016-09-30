Feature: User login
  In order to login in application
  As an OroCRM admin
  I need to be able to authenticate

# Can't test this scenario on commerce-crm application

#Scenario: Success login
#  Given I am on "/user/login"
#  And I fill "Login" form with:
#    | Username | admin |
#    | Password | admin |
#  And I press "Log in"
#  And I should be on "/"

Scenario: Fail login
  Given I login as "non_existed_user" user with "non_existed_password" password
  And I should see "Invalid user name or password."
