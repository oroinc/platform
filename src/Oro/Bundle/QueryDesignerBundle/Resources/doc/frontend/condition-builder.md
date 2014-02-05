# Condition Builder Component

## Responsibility
Condition builder is a [jQuery-UI widget], responsible for rendering UI control which allows to build a structure with nested conditions such as:
```js
    [
        { /* ... */ },
        'AND',
        [
            { /* ... */ },
            'OR',
            { /* ... */ },
        ]
    ]
```
Where:
 - `array` is a **Conditions Group**;
 - `object` is a **Condition Item**;
 - `string` is an **Operator**.

## Usage Example
The widget requires predefined certain HTML structure. List of available condition criteria and container for building process:
```html
    <ul class="criteria-list" id="criteria-list">
        <li class="option" data-criteria="condition-item"
            data-module="somebundle/js/some-condition-item"
            data-widget="someConditionItem"
            data-options="{}">
            Condition Item
        </li>
        <li class="option" data-criteria="conditions-group">
            Conditions Group
        </li>
    </ul>
    <div class="condition-builder" id="condition-builder"></div>
    <input type="hidden" id="conditions" name="conditions"
        value="{{ value|json_encode }}"/>
    <script type="text/javascript">
        $('#condition-builder').conditionBuilder({
            criteriaListSelector: '#criteria-list',
            sourceValueSelector: '#conditions'
        });
    </script>
```
The widget is appended to a container and a criteria list is defined with the only required option `criteriaListSelector`. Each criteria should have `data-criteria` attribute which allows to distinguish them.

Optionally can be defined a field for result value, `sourceValueSelector` option is responsible for that. On widget's initialization, the value will be read and conditions structure restored. On any change of structure, a condition or an operator -  the field's value will be updated.

There's two preset kinds of condition:
 - `conditions-group`, new sub-group (`conditionBuilder` already has a root group by itself);
 - `condition-item`, some kind of condition (useful if there's no other conditions).

Condition Item criteria element should have three `data` attributes in order to `conditionBuilder` could initialize sub-widget which is responsible for that condition-item. Attributes are:
 - `data-module` - name of AMD module which contains sub-widget definition;
 - `data-widget` - widget's name;
 - `data-options` - JSON-string with options for sub-widget.

## Condition Item with Custom Criteria
It's possible to define custom criteria. Just set `data-criteria` attribute with your own criteria name and condition's value object will be extended with extra property `criteria`. For example:
```html
    <ul class="criteria-list" id="criteria-list">
        <li class="option" data-criteria="matrix-condition"
            data-module="somebundle/js/matrix-condition"
            data-widget="matrixCondition"
            data-options="{}">
            Matrix Condition
        </li>
        <li class="option" data-criteria="condition-item"
            data-module="somebundle/js/some-condition-item"
            data-widget="someConditionItem"
            data-options="{}">
            Condition Item
        </li>
        <li class="option" data-criteria="conditions-group">
            Conditions Group
        </li>
    </ul>
```
Then value will have following structure:
```js
    [
        { /* ... */, criteria: "matrix-condition" },
        'AND',
        [
            { /* ... */ }, // usual condition-item
            'OR',
            { /* ... */, criteria: "matrix-condition" },
        ]
    ]
```

## Options
 - `sortable` - common options for both sortable containers **conditions group** and **criteria list**, see options for [jQuery-UI sortable];
 - `conditionsGroup` - specific options for sortable widget of **conditions group**;
 - `criteriaList` - specific options for sortable widget of **criteria list**;
 - `operations` - array with allowed operations (default `['AND', 'OR']`);
 - `criteriaListSelector` - jQuery selector for criteria **criteria list**;
 - `sourceValueSelector` - jQuery selector for an input which keeps result value;
 - `helperClass` - CSS class for a grabbing element (default `'ui-grabbing'`);
 - `conditionHTML` - HTML for a condition element (default `'<li class="condition controls" />'`);
 - `conditionItemHTML` - HTML for a content of condition item (default `'<div class="condition-item" />'`);
 - `conditionsGroupHTML` - HTML for a content of conditions group (default `'<ul class="conditions-group" />'`);
 - `validation` - an object with validation rules. Where a key - criteria name, value - object with validation rules. See validation in OroFormBundle.

## Methods
Beside standard public methods of [jQuery-UI widget], `conditionBuilder` has two additional methods:
 - `getValue()` - returns current value (array of conditions)
 - `setValue(value)` - sets new value (existing structure will be removed and new restored from the value)

Example:
```js
    var currentValue = $('#builder').conditionBuilder('getValue');
    var newValue = [
        [{/* ... */}, 'AND', {/* ... */}],
        'OR',
        {/* ... */}
    ];
    $('#builder').conditionBuilder('setValue', newValue);
```

## Requirements for Condition Widget
Widget which implements some Condition Item, on value change should:
 - set the value into `data` of element it is appended to `this.element.data('value', value)`;
 - trigger DOM-event `'changed'` on its element `this.element.trigger('changed')`.

[jQuery-UI widget]: <http://api.jqueryui.com/jQuery.widget/>
[jQuery-UI sortable]: <http://api.jqueryui.com/sortable/>
