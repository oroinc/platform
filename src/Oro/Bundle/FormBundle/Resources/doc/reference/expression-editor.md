# Expression editor

## <a name="util">ExpressionEditorUtil</a>

Source: [`oroform/js/expression-editor-util`](../../public/js/expression-editor-util.js)

Implements core autocomplete and validate functionality for expressions.
Expression editor:

* Parses an expression with the extension of `ExpressionLanguage`
[[?]](../../../../ExpressionLanguageBundle/Resources/public/js/extend/expression-language.js)
(see [documentation](../../../../ExpressionLanguageBundle/Resources/doc/js/expression-language-extension.md)).

* Validates an expression with the particular `ExpressionOperandTypeValidator` validator
[[?]](../../public/js/expression-operand-type-validator.js).

* Uses `EntityStructureDataProvider`
[[?]](../../../../EntityBundle/Resources/public/js/app/services/entity-structure-data-provider.js)
(see [documentation](../../../../EntityBundle/Resources/doc/client-side/entity-structure-data-provider.md)) to build
autocomplete items from the entity fields.

You can configure the allowed operations with the following options:

- `entityDataProvider` - instance of EntityStructureDataProvider (required)
- `dataSourceNames` - array of entity aliases (or class names) that require a data source, it needs for validation,
rendering and determine of correct cursor position
- `itemLevelLimit` - used to prevent of creation infinite chain of entity fields, can be `2` or more. For instance,
if you set it to 2, you can choose `product.id` but `product.category.id` would not be allowed 
- `allowedOperations` - array containing names of operation groups
- `operations` - object containing names of operation groups like keys and arrays of operation strings like values, e.g.

   ````
   {
        math: ['+', '-', '%', '*', '/'],
        bool: ['and', 'or']
        ...
   }
   ````
- `supportedNames` - array containing entity aliases that can be used in the expression e.g.

        ["product", "pricelist"]

In case `entityDataProvider` is absent in options the ExpressionEditorUtil throws an exeptions.
The same in case `itemLevelLimit` less then 2.

Contains public methods:
- `getAutocompleteData` - builds autocomplete data object from expression and cursor position
- `updateAutocompleteItem` - puts new value into autocomplete data and updates cursor position to end of it
- `updateDataSourceValue` - sets new data source value into autocomplete data
- `validate` - validates expression syntax
- `findFieldChain` - finds accesses to the entity fields in the expression and returns them like the info objects

For validation, the utility parses an expression with the `ExpressionLanguage` instance. If 
the expression is parsed correctly it is also checked with the 
`ExpressionOperandTypeValidator` (see below).

## <a name="validator">ExpressionOperandTypeValidator</a>

Source: [`oroform/js/expression-operand-type-validator`](../../public/js/expression-operand-type-validator.js)

Uses `ASTNodeWrapper` [[?]](../../../../ExpressionLanguageBundle/Resources/public/js/ast-node-wrapper.js)
(see [documentation](../../../../ExpressionLanguageBundle/Resources/doc/js/ast-node-wrapper.md)) to analyze the parsed expression.

Constructor supports the following options:

- `entities` - special objects that contain info about entity fields that can be used in expression
- `itemLevelLimit` - determines how deep level fields of entity can be used in expression
- `operations` - list of all allowed operations
- `isConditionalNodeAllowed` - determines possibility to use in expression ternary operators

It has only one public method, `expectValid`, that gets the parsed expression and throws `TypeError` if the expression is invalid.

## <a name="view">ExpressionEditorView</a>

Source: [`oroform/js/app/views/expression-editor-view`](../../public/js/app/views/expression-editor-view.js)

Used `ExpressionEditorUtil` and `typeahead` widget to provide autocomplete and validate UI for text fields. 

Except options that the view passes to ExpressionEditUtil, it has own ones:

- `delay` - sets latency of appearing autocomplete's dropdown or validation after user makes changes
- `dataSource` - object containing html of data source widgets (e.g. Select2 components), where keys are names 
of entities that use this data source to specify particular instance of entity. A widget will be rendered when you type
or choose appropriate entity name. E.g. when option is
    ````
    {
        email: '<select><option value="1">Option 1</option><option value="2">Option 2</option></select>'
    }
    ````

and user type `email` in the editor

## How to Use

Create `ExpressionEditorView` and use its util instance for validation (assuming a provider instance was created before):

    var ExpressionEditorView = require('oroform/js/app/views/expression-editor-view');

    var editorView = new ExpressionEditorView({
        entityDataProvider: provider,
        itemLevelLimit: 4,
        allowedOperations: ['math', 'equality'],
        operations: {
            math: ['+', '-'],
            equality: ['==']
        },
        supportedNames: ['email']
    });
    console.log(editorView.util.validate('email.id == 1'))

How to create a provider see into its [documentation](../../../../EntityBundle/Resources/doc/client-side/entity-structure-data-provider.md).

## <a name="component">ExpressionEditorComponent</a>

Source: [`oroform/js/app/components/expression-editor-component`](../../public/js/app/components/expression-editor-component.js)

For convenient using of view there is particular component. It's designed to create entity data provider and then to 
create a view instance

    {{ form_widget(form.expression, {'attr': {
        'data-page-component-module': 'oroform/js/app/components/expression-editor-component',
        'data-page-component-options': initializeOptions
    }}) }}
