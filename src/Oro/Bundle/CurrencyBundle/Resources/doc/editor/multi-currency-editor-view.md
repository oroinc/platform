<a name="module_MultiCurrencyEditorView"></a>
## MultiCurrencyEditorView ⇐ [TextEditorView](../../../../FormBundle/Resources/doc/editor/text-editor-view.md)
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

**Extends:** [TextEditorView](../../../../FormBundle/Resources/doc/editor/text-editor-view.md)  

| Param | Type | Description |
| --- | --- | --- |
| options | `Object` | Options container |
| options.model | `Object` | Current row model |
| options.fieldName | `string` | Field name to edit in model |
| options.placeholder | `string` | Placeholder translation key for an empty element |
| options.placeholder_raw | `string` | Raw placeholder value. It overrides placeholder translation key |
| options.validationRules | `Object` | Validation rules. See [documentation here](../../../../FormBundle/Resources/doc/reference/js_validation.md#conformity-server-side-validations-to-client-once) |
| options.choices | `Object` | Array of codes of available currencies |


* [MultiCurrencyEditorView](#module_MultiCurrencyEditorView) ⇐ [TextEditorView](./text-editor-view.md)
  * [.MINIMUM_RESULTS_FOR_SEARCH](#module_MultiCurrencyEditorView#MINIMUM_RESULTS_FOR_SEARCH)
  * [.parseRawValue(value)](#module_MultiCurrencyEditorView#parseRawValue) ⇒ `Object`
  * [.getValue()](#module_MultiCurrencyEditorView#getValue) ⇒ `String`
  * [.getCurrencyData()](#module_MultiCurrencyEditorView#getCurrencyData) ⇒ `Array`

<a name="module_MultiCurrencyEditorView#MINIMUM_RESULTS_FOR_SEARCH"></a>
### multiCurrencyEditorView.MINIMUM_RESULTS_FOR_SEARCH
Option for select2 widget to show or hide search input for list of currencies

**Kind**: instance property of [MultiCurrencyEditorView](#module_MultiCurrencyEditorView)  
**Access:** protected  
<a name="module_MultiCurrencyEditorView#parseRawValue"></a>
### multiCurrencyEditorView.parseRawValue(value) ⇒ `Object`
Convert string presetation of value to object with 'currency' and 'amount' fields

**Kind**: instance method of [MultiCurrencyEditorView](#module_MultiCurrencyEditorView)  

| Param | Type | Description |
| --- | --- | --- |
| value | `String` | in format 'currency_code+amount' |

<a name="module_MultiCurrencyEditorView#getValue"></a>
### multiCurrencyEditorView.getValue() ⇒ `String`
Collects values from DOM elements and converts them to string format like EUR100.0000

**Kind**: instance method of [MultiCurrencyEditorView](#module_MultiCurrencyEditorView)  
<a name="module_MultiCurrencyEditorView#getCurrencyData"></a>
### multiCurrencyEditorView.getCurrencyData() ⇒ `Array`
Prepares array of objects that presents select options in dropdown

**Kind**: instance method of [MultiCurrencyEditorView](#module_MultiCurrencyEditorView)  
