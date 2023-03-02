@regression
Feature: Password complexity feature
  In order to increase safety of users
  As Administrator
  I need to define complexity of user passwords


  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I change configuration options:
      | oro_user.password_min_length    | 4    |
      | oro_user.password_special_chars | true |

  Scenario: Check dashboard user creation with full password complexity
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/User Management/Users
    And click "Create User"
    And I fill "User Form" with:
      | Enabled             | Enabled          |
      | Username            | mattjohnes       |
      | First Name          | Matt             |
      | Last Name           | Johnes           |
      | Primary Email       | orotest@test.com |
      | Roles               | Administrator    |
      | OroCRM Organization | true             |

  Scenario Outline:
    Given I fill "User Form" with:
      | Password | <Password> |
      | Re-Enter Password | <Re-Enter Password> |
    Then I should see validation errors:
      | Password | <Validation error> |
    When I save and close form
    Then I should see validation errors:
      | Password | <Validation error> |
    And Page title equals to "Create User - Users - User Management - System"

    Examples:
      | Password | Re-Enter Password | Validation error                                                                                                   |
      | AAA      | AAA               | The password must be at least 4 characters long and include a lower case letter, a number, and a special character |
      | AAAA     | AAAA              | The password must include a lower case letter, a number and a special character                                    |
      | AAAAa    | AAAAa             | The password must include a number and a special character                                                         |
      | AAAAa1   | AAAAa1            | The password must include a special character                                                                      |
      | aaa1!    | aaa1!             | The password must include an upper case letter                                                                     |
      | AAA1^    | AAA1^             | The password must include a lower case letter                                                                      |
      | AAAa*    | AAAa*             | The password must include a number                                                                                 |

  Scenario: Create user with full password complexity
    Given I fill "User Form" with:
      | Password          | AAAAa1! |
      | Re-Enter Password | AAAAa1! |
    And I save and close form
    Then should see "User saved" flash message
    And click logout in user menu
    When I fill "Login Form" with:
      | Username | mattjohnes |
      | Password | AAAAa1!    |
    And I click "Log in"
    Then I should be on Admin Dashboard page

  Scenario: Check customer user creation with full password complexity
    Given I proceed as the Buyer
    And I am on the homepage
    And click "Register"
    And I fill "Registration Form" with:
      | Company Name  | TestCompany              |
      | First Name    | Amanda                   |
      | Last Name     | Cole                     |
      | Email Address | AmandaRCole1@example.org |

  Scenario Outline:
    Given I fill "Registration Form" with:
      | Password         | <Password>         |
      | Confirm Password | <Confirm Password> |
    Then I should see validation errors:
      | Password | <Validation error> |
    When I click "Create An Account"
    Then I should see validation errors:
      | Password | <Validation error> |
    And Page title equals to "Registration"

    Examples:
      | Password | Confirm Password | Validation error                                                                                                   |
      | AAA      | AAA              | The password must be at least 4 characters long and include a lower case letter, a number, and a special character |
      | AAAA     | AAAA             | The password must include a lower case letter, a number and a special character                                    |
      | AAAAa    | AAAAa            | The password must include a number and a special character                                                         |
      | AAAAa1   | AAAAa1           | The password must include a special character                                                                      |
      | aaa1!    | aaa1!            | The password must include an upper case letter                                                                     |
      | AAA1^    | AAA1^            | The password must include a lower case letter                                                                      |
      | AAAa*    | AAAa*            | The password must include a number                                                                                 |

  Scenario: Create customer user with full password complexity
    Given I fill "Registration Form" with:
      | Password         | AAAAa1! |
      | Confirm Password | AAAAa1! |
    When I click "Create An Account"
    Then I should see "Please check your email to complete registration" flash message
    And I proceed as the Admin
    And go to Customers/Customer Users
    And click view "AmandaRCole1@example.org" in grid
    And click "Confirm"
    And I proceed as the Buyer
    When fill form with:
      | Email Address | AmandaRCole1@example.org |
      | Password      | AAAAa1!                  |
    And click "Sign In"
    Then should see "Signed in as: Amanda Cole"
    And click "Sign Out"

  Scenario: Uncheck all user password complexity options (dashboard user)
    Given I proceed as the Admin
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/User Settings" on configuration sidebar
    And uncheck "Use default" for "Require a number" field
    And uncheck "Use default" for "Require a lower case letter" field
    And uncheck "Use default" for "Require an upper case letter" field
    And uncheck "Require a number"
    And uncheck "Require an upper case letter"
    And uncheck "Require a lower case letter"
    And uncheck "Require a special character"
    And I click "Save settings"
    And I go to System/User Management/Users
    And click "Create User"
    And I fill "User Form" with:
      | Enabled             | Enabled            |
      | Username            | roothmio           |
      | First Name          | Rooth              |
      | Last Name           | Mio                |
      | Primary Email       | orotest+1@test.com |
      | Roles               | Administrator      |
      | OroCRM Organization | true               |

  Scenario Outline:
    Given I fill "User Form" with:
      | Password          | <Password>          |
      | Re-Enter Password | <Re-Enter Password> |
    Then I should not see validation errors:
      | Password | <Validation error> |

    Examples:
      | Password | Re-Enter Password | Validation error                                                                |
      | AAAA     | AAAA              | The password must include a lower case letter, a number and a special character |
      | AAAAa    | AAAAa             | The password must include a number and a special character                      |
      | AAAAa1   | AAAAa1            | The password must include a special character                                   |
      | aaa1!    | aaa1!             | The password must include an upper case letter                                  |
      | AAA1!    | AAA1!             | The password must include a lower case letter                                   |
      | AAAa!    | AAAa!             | The password must include a number                                              |

  Scenario: Create user without full password complexity (only minimal password length)
    Given I fill "User Form" with:
      | Password          | 1111 |
      | Re-Enter Password | 1111 |
    And I save and close form
    Then should see "User saved" flash message
    And click logout in user menu
    When I fill "Login Form" with:
      | Username | roothmio |
      | Password | 1111     |
    And I click "Log in"
    Then I should be on Admin Dashboard page

  Scenario: Uncheck all user password complexity options (Frontstore user)
    Given I proceed as the Buyer
    And click "Register"
    And I fill "Registration Form" with:
      | Company Name  | TestCompany              |
      | First Name    | Ellen                    |
      | Last Name     | Rowel                    |
      | Email Address | EllenRRowel1@example.org |

  Scenario Outline:
    Given I fill "Registration Form" with:
      | Password         | <Password>         |
      | Confirm Password | <Confirm Password> |
    Then I should not see validation errors:
      | Password | <Validation error> |

    Examples:
      | Password | Confirm Password | Validation error                                                                |
      | AAAA     | AAAA             | The password must include a lower case letter, a number and a special character |
      | AAAAa    | AAAAa            | The password must include a number and a special character                      |
      | AAAAa1   | AAAAa1           | The password must include a special character                                   |
      | aaa1!    | aaa1!            | The password must include an upper case letter                                  |
      | AAA1^    | AAA1^            | The password must include a lower case letter                                   |
      | AAAa*    | AAAa*            | The password must include a number                                              |

  Scenario: Create customer user without full password complexity (only minimal password length)
    Given I fill "Registration Form" with:
      | Password         | 1111 |
      | Confirm Password | 1111 |
    When I click "Create An Account"
    Then I should see "Please check your email to complete registration" flash message
    And I proceed as the Admin
    And go to Customers/Customer Users
    And click view "EllenRRowel1@example.org" in grid
    And click "Confirm"
    And I proceed as the Buyer
    When fill form with:
      | Email Address | EllenRRowel1@example.org |
      | Password      | 1111                     |
    And click "Sign In"
    Then should see "Signed in as: Ellen Rowel"
    And click "Sign Out"
