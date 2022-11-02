@ticket-BB-20362

Feature: Correct HTML Purifier error during products import
  In order to use product import feature
  As a Site Administrator
  I want to be able to have HTML Purifier errors listed in the resulting email

  Scenario: Set invalid application URL
    Given I login as administrator
    When I go to Products/ Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | attributeFamily.code | names.default.value | descriptions.default.value                                                            | sku   | status  | type   | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision |
      | default_family       | Test Product 1      | <div><p class="product-view-desc">This medical identifications tag is a beautiful</p> | PSKU1 | enabled | simple | in_stock            | set                            | 1                              |
    And I import file
    Then Email should contains the following "Errors: 1 processed: 0, read: 1, added: 0, updated: 0, replaced: 0" text
    When I follow "Error log" link from the email
    Then I should see "Error in row \#1. descriptions[default].wysiwyg: Please remove not permitted HTML-tags in the content field:"
    And I should see "- <div> tag started on line 1 should be closed by end of document (near <div><p class=\"product-vi...)."
