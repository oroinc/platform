@regression
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroEmailBundle:templates.yml
Feature: Email Template Inheritance

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Configure German Localization
    Given I proceed as the Admin
    And I login as administrator
    And I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    When I fill form with:
      | Enabled Localizations | [English (United States), German_Loc] |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Update default email template
    Given I go to System/ Emails/ Templates
    And I filter Template Name as is equal to "order_confirmation_email"
    And I click "edit" on first row in grid
    When fill "Email Template Form" with:
      | Subject | Ingerited subject                                                                                                                   |
      | Content | {% extends oro_get_email_template('order_confirmation_email_parent') %}{% block child %}Child Inherited Content{% endblock child %} |
    And I click "German"
    When fill "Email Template Form" with:
      | Subject Fallback | false                                                                                                                                        |
      | Content Fallback | false                                                                                                                                        |
      | Subject          | German Ingerited subject                                                                                                                     |
      | Content          | {% extends oro_get_email_template('order_confirmation_email_parent') %}{% block child %} German Child Inherited Content {% endblock child %} |
    Then I save and close form
    And I should see "Template saved" flash message

  Scenario: Check email template inheritance
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And Buyer is on "List 1" shopping list
    When I press "Create Order"
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I uncheck "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And Email should contains the following:
      | From    | admin@example           |
      | To      | AmandaRCole@example.org |
      | Subject | Ingerited subject       |
      | Body    | Default Parent Content  |
      | Body    | Child Inherited Content |
      | Body    | Copyright               |

  Scenario: Check localized email template inheritance
    Given I proceed as the Buyer
    And I select "German Localization" localization
    And Buyer is on "List 1" shopping list
    When I press "Create Order"
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I press "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And Email should contains the following:
      | From    | admin@example                  |
      | To      | AmandaRCole@example.org        |
      | Subject | German Ingerited Subject       |
      | Body    | German Parent Content          |
      | Body    | German Child Inherited Content |
      | Body    | Copyright                      |
