Expression editor
=================

<a name="util">ExpressionEditorUtil:</a>
--------------------
Source: [`oroform/js/expression-editor-util`](../../public/js/expression-editor-util.js)

Implements core autocomplete and validate functionality for expressions.
It uses `EntityStructureDataProvider` [[?]](../../../../EntityBundle/Resources/public/js/app/services/entity-structure-data-provider.js) (see [documentation](../../../../EntityBundle/Resources/doc/client-side/entity-structure-data-provider.md)) for building autocomplete items from entity fields and validation of expression.
You can configure allowed operations by options.

Supports options:

- `entityDataProvider` - instance of EntityStructureDataProvider (required)
- `dataSourceNames` - array of entity aliases (or class names) that require a data source, it needs for validation,
rendering and determine of correct cursor position
- `itemLevelLimit` - used to prevent of creation infinile chain of entity fields, can be `2` or more. For instance,
if you set it to 2, you can choose `product.id` but `product.category.id` wouldn't be allowed 
- `allowedOperations` - array containing names of operation groups
- `operations` - object containing names of operation groups like keys and arrays of operation strings like values, e.g.

   ````
   {
        math: ['+', '-', '%', '*', '/'],
        bool: ['and', 'or']
        ...
   }
   ````
- `rootEntities` - array containing entity aliases (or class names if entity has no alias), that can be used 
in the expression e.g.

        ["product", "pricelist", "Oro\\Bundle\\SomeBundle\\Entity\\SomeEntity"]

In case `entityDataProvider` is absent in options the ExpressionEditorUtil throws an exeptions.
The same in case `itemLevelLimit` less then 2.

Contains public methods:
- `getAutocompleteData` - builds autocomplete data object from expression and cursor position
- `updateAutocompleteItem` - puts new value into autocomplete data and updates cursor position to end of it
- `updateDataSourceValue` - sets new data source value into autocomplete data
- `validate` - validates expression syntax

For validation the util transforms expression into safe native JS code and executes it. If there is no exeption - 
expression is valid.

For such transformation first of all util cleans expression from allowed and not allowed words and symbols, 
see `regex.nativeReplaceAllowedBeforeTest` and `regex.nativeFindNotAllowed` properties. Then entity fields transforms 
into their types (e.g. string, integer) and then transforms to appropriate primitive
(e.g. integer => 0, string => '' etc.). Some operations that difference with js syntax are transformed as well 
(e.g. and => &&). To transformation uses `itemToNativeJS` property.

<a name="view">ExpressionEditorView</a>
--------------------
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

How to Use
----------

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
        rootEntities: ['email']
    });
    console.log(editorView.util.validate('email.id == 1'))

How to create a provider see into its [documentation](../../../../EntityBundle/Resources/doc/client-side/entity-structure-data-provider.md).

<a name="component">ExpressionEditorComponent</a>
-------------------------

Source: [`oroform/js/app/components/expression-editor-component`](../../public/js/app/components/expression-editor-component.js)

For convenient using of view there is particular component. It's designed to create entity data provider and then to 
create a view instance

    {{ form_widget(form.expression, {'attr': {
        'data-page-component-module': 'oroform/js/app/components/expression-editor-component',
        'data-page-component-options': initializeOptions
    }}) }}
