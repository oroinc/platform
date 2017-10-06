# Condition Builder Component

## Responsibility
`ConditionBuilderView` responsible for rendering UI control which allows to build a structure with nested conditions such as:
```js
    [
        { /* ... */ },
        "AND",
        [
            { /* ... */ },
            "OR",
            { /* ... */ }
        ]
    ]
```
Where:
 - `array` is a **Conditions Group**;
 - `object` is a **Condition Item**;
 - `string` is an **Operator**.

## Usage Example
`ConditionBuilderView` requires predefined certain HTML structure:
```html
    <div class="condition-builder">
        <ul class="criteria-list">
            <li class="option" data-criteria="condition-item"
                data-module="somebundle/js/app/views/some-condition-view"
                data-options="{}">
                Condition Item
            </li>
            <li class="option" data-criteria="conditions-group">
                Conditions Group
            </li>
        </ul>
        <div class="condition-container"></div>
    </div>
```
With two block: list of available condition criteria and container for building process. Each criteria of the list should have `data-criteria` attribute which allows to distinguish them.

There's two preset kinds of condition:
 - `conditions-group`, new sub-group (`ConditionBuilderView` already has a root group by itself);
 - `condition-item`, some kind of condition (useful if there's no other conditions).

Condition Item criteria element should have two `data` attributes in order to `ConditionBuilderView` could 
initialize a `ConditionView`, which is responsible for that condition-item. Attributes are:
 - `data-module` — name of AMD module which contains a `ConditionView` definition;
 - `data-options` — JSON-string with options for a `ConditionView`.

## Condition Item with Custom Criteria
It's possible to define custom criteria. Just set `data-criteria` attribute with your own criteria name. For example:
```html
    <div class="condition-builder">
        <ul class="criteria-list" id="criteria-list">
            <li class="option" data-criteria="matrix-condition"
                data-module="somebundle/js/app/views/matrix-condition-view"
                data-options="{}">
                Matrix Condition
            </li>
            <li class="option" data-criteria="condition-item"
                data-module="somebundle/js/app/views/some-condition-view"
                data-options="{}">
                Condition Item
            </li>
            <li class="option" data-criteria="conditions-group">
                Conditions Group
            </li>
        </ul>
    </div>
```
And condition's value object will be extended with extra property `criteria`:
```json
    [
        {/* ... */, "criteria": "matrix-condition"},
        "AND",
        [
            {/* ... */}, // usual condition-item
            "OR",
            {/* ... */, "criteria": "matrix-condition"}
        ]
    ]
```

## Options for ConditionBuilderView
 - `value` — initial value, array of condition
 - `sortable` — common options for both sortable containers **conditions group** and **criteria list**, see options for [jQuery-UI sortable];
 - `conditionsGroup` — specific options for sortable widget of **conditions group**;
 - `criteriaList` — specific options for sortable widget of **criteria list**;
 - `operations` — array with allowed operations (default `['AND', 'OR']`);
 - `criteriaListSelector` — jQuery selector for criteria **criteria list** (default `'.criteria-list'`);
 - `conditionContainerSelector` — jQuery selector for criteria **condition container** (default `'.condition-container'`);
 - `helperClass` — CSS class for a grabbing element (default `'ui-grabbing'`);
 - `validation` — an object with validation rules. Where a key — criteria name, value — object with validation rules. See validation in OroFormBundle.

## Methods
`ConditionBuilderView` has two own public methods:
 - `getValue()` — returns current value (array of conditions)
 - `setValue(value)` — sets new value (existing structure will be removed and new restored from the value)

Example:
```js
    var conditionBuilderView = new ConditionBuilderView({
        autoRender: true,
        value: [{/*...*/, 'OR', /*...*/}]
    });

    conditionBuilderView.getValue(); // returns current value
    
    conditionBuilderView.setValue([ // allows to change value
        [{/* ... */}, 'AND', {/* ... */}],
        'OR',
        {/* ... */}
    ]);
```

[jQuery-UI sortable]: <http://api.jqueryui.com/sortable/>
