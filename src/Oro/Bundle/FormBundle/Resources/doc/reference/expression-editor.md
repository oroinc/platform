Expression editor
=================

ExpressionEditorUtil:
--------------------
Source: [`oroform/js/expression-editor-util`](../../public/js/expression-editor-util.js)

Implements core autocomplete and validate functionality for expressions.
You can configure allowed expression syntax by options.

Contains public functions:
- `validate` - Validate expression syntax
- `getAutocompleteData` - Build autocomplete data by expression and cursor position
- `updateAutocompleteItem` - Insert into autocomplete data new item
- `updateDataSourceValue` - Set new data source value into autocomplete data

ExpressionEditorView
--------------------
Source: [`oroform/js/app/views/expression-editor-view`](../../public/js/app/views/expression-editor-view.js)

Used `ExpressionEditorUtil` and `typeahead` widget to provide autocomplete and validate UI for text fields. 

How to Use
----------

Use `ExpressionEditorView` for text fields:

    {{ form_widget(form.expression, {'attr': {
        'data-page-component-module': 'oroui/js/app/components/view-component',
        'data-page-component-options': initializeOptions|merge({
            view: 'oroform/js/app/views/expression-editor-view',
        })
    }}) }}

Use `ExpressionEditorUtil` for form validation:  

    var ExpressionEditorUtil = require('oroform/js/expression-editor-util');
    var expressionEditorUtil = new ExpressionEditorUtil(initializeOptions);
    expressionEditorUtil.validate();

Validation
----------

For validation, we transform expression into safe native JS code and execute it. 

For transformation we use `ExpressionEditorUtil.itemToNativeJS` property and 
list of allowed and not allowed words/symbols, see `ExpressionEditorUtil.regex.nativeReplaceAllowedBeforeTest` and `ExpressionEditorUtil.regex.nativeFindNotAllowed`

Options
-------
Both util and view expect same initialize options. The most important of them:

- Operations groups, list of all supported operations. Default value:

        operations: {
            math: ['+', '-', '%', '*', '/'],
            bool: ['and', 'or'],
            equality: ['==', '!='],
            compare: ['>', '<', '<=', '>='],
            inclusion: ['in', 'not in'],
            like: ['matches']
        }

- Allowed operations groups. Default value:

        allowedOperations: ['math', 'bool', 'equality', 'compare', 'inclusion', 'like']

- Entities and fields, that you can use. Doesn't have default value, you should specify it for each view or util. Format:
        
        entities: {
            root_entities: {
                "Oro\\Bundle\\ProductBundle\\Entity\\Product": "product",
                "Oro\\Bundle\\PricingBundle\\Entity\\PriceList": "pricelist"
            },
            fields_data: {
                "Oro\\Bundle\\ProductBundle\\Entity\\Product": {
                    id: {
                        label: "Id",
                        type: "integer"
                    },
                    category: {
                        label: "Category",
                        type: "relation",
                        relation_alias: "Oro\\Bundle\\CatalogBundle\\Entity\\Category"
                    },
                    ...
                },
                "Oro\\Bundle\\CatalogBundle\\Entity\\Category": {
                    id: {
                        label: "Id",
                        type: "integer"
                    },
                    ...
                }
                "Oro\\Bundle\\PricingBundle\\Entity\\PriceList": {...}
            }
        }

- Limit of nested entities level. For example, if set this option to 1, `category` will be excluded from allowed items list. Default value:
 
        itemLevelLimit: 3
        
- Widgets to choose specific entities. This widget will be rendered when you type `pricelist`. Format:

        dataSource: {
            pricelist: "<select><option value='1'>Option 1</option><option value='2'>Option 2</option><option value='3'>Option 3</option></select>"
        }
