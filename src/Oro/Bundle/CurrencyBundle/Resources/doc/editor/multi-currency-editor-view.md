<a name="module_MultiCurrencyEditorView"></a>
## MultiCurrencyEditorView ⇐ <code>[TextEditorView](../../../../FormBundle/Resources/doc/editor/text-editor-view.md)</code>
Multi currency cell content editor.

### Column configuration samples:
``` yml
datagrids:
  {grid-uid}:
    inline_editing:
      enable: true
    # <grid configuration> goes here
    columns:
      # Sample 1. Mapped by number frontend type
      {column-name-1}:
        frontend_type: <multi-currency>
      # Sample 2. Full configuration
      {column-name-2}:
        inline_editing:
          editor:
            view: orocurrency/js/app/views/editor/multi-currency-editor-view
            view_options:
              placeholder: '<placeholder>'
              css_class_name: '<class-name>'
          validation_rules:
            NotBlank: ~
        multicurrency_config:
          original_field: '<original_field>'
          value_field: '<value_field>'
          currency_field: '<currency_field>'
```

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:-----------
inline_editing.editor.view_options.placeholder      | Optional. Placeholder translation key for an empty element
inline_editing.editor.view_options.placeholder_raw  | Optional. Raw placeholder value
inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
inline_editing.validation_rules | Optional. Validation rules. See [documentation](../../../../FormBundle/Resources/doc/reference/js_validation.md#conformity-server-side-validations-to-client-once)
multicurrency_config.original_field | Field that contains combined currency value, like EUR100.0000
multicurrency_config.value_field | Field that contains amount of currency value
multicurrency_config.currency_field | Field that contains code of currency (e.g. EUR)


### Constructor parameters

**Extends:** <code>[TextEditorView](../../../../FormBundle/Resources/doc/editor/text-editor-view.md)</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.model | <code>Object</code> | Current row model |
| options.fieldName | <code>string</code> | Field name to edit in model |
| options.placeholder | <code>string</code> | Placeholder translation key for an empty element |
| options.placeholder_raw | <code>string</code> | Raw placeholder value. It overrides placeholder translation key |
| options.validationRules | <code>Object</code> | Validation rules. See [documentation here](../../../../FormBundle/Resources/doc/reference/js_validation.md#conformity-server-side-validations-to-client-once) |
| options.choices | <code>Object</code> | Array of codes of available currencies |


* [MultiCurrencyEditorView](#module_MultiCurrencyEditorView) ⇐ <code>[TextEditorView](./text-editor-view.md)</code>
  * [.MINIMUM_RESULTS_FOR_SEARCH](#module_MultiCurrencyEditorView#MINIMUM_RESULTS_FOR_SEARCH)
  * [.parseRawValue(value)](#module_MultiCurrencyEditorView#parseRawValue) ⇒ <code>Object</code>
  * [.getValue()](#module_MultiCurrencyEditorView#getValue) ⇒ <code>String</code>
  * [.getCurrencyData()](#module_MultiCurrencyEditorView#getCurrencyData) ⇒ <code>Array</code>

<a name="module_MultiCurrencyEditorView#MINIMUM_RESULTS_FOR_SEARCH"></a>
### multiCurrencyEditorView.MINIMUM_RESULTS_FOR_SEARCH
Option for select2 widget to show or hide search input for list of currencies

**Kind**: instance property of <code>[MultiCurrencyEditorView](#module_MultiCurrencyEditorView)</code>  
**Access:** protected  
<a name="module_MultiCurrencyEditorView#parseRawValue"></a>
### multiCurrencyEditorView.parseRawValue(value) ⇒ <code>Object</code>
Convert string presetation of value to object with 'currency' and 'amount' fields

**Kind**: instance method of <code>[MultiCurrencyEditorView](#module_MultiCurrencyEditorView)</code>  

| Param | Type | Description |
| --- | --- | --- |
| value | <code>String</code> | in format 'currency_code+amount' |

<a name="module_MultiCurrencyEditorView#getValue"></a>
### multiCurrencyEditorView.getValue() ⇒ <code>String</code>
Collects values from DOM elements and converts them to string format like EUR100.0000

**Kind**: instance method of <code>[MultiCurrencyEditorView](#module_MultiCurrencyEditorView)</code>  
<a name="module_MultiCurrencyEditorView#getCurrencyData"></a>
### multiCurrencyEditorView.getCurrencyData() ⇒ <code>Array</code>
Prepares array of objects that presents select options in dropdown

**Kind**: instance method of <code>[MultiCurrencyEditorView](#module_MultiCurrencyEditorView)</code>  
