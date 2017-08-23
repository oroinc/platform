<a name="module_SelectEditorView"></a>
## SelectEditorView ⇐ <code>[TextEditorView](./text-editor-view.md)</code>
Select cell content editor. The cell value should be a value field.
The grid will render a corresponding label from the `options.choices` map.
The editor will use the same mapping

### Column configuration samples:
``` yml
datagrids:
  {grid-uid}:
    inline_editing:
      enable: true
    # <grid configuration> goes here
    columns:
      # Sample 1. Mapped by frontend type
      {column-name-1}:
        frontend_type: select
        choices: # required
          key-1: First
          key-2: Second
      # Sample 2. Full configuration
      {column-name-2}:
        choices: # required
          key-1: First
          key-2: Second
        inline_editing:
          editor:
            view: oroform/js/app/views/editor/select-editor-view
            view_options:
              placeholder: '<placeholder>'
              css_class_name: '<class-name>'
          validation_rules:
            NotBlank: ~
          save_api_accessor:
              route: '<route>'
              query_parameter_names:
                 - '<parameter1>'
                 - '<parameter2>'
```

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:---------------------------------------
choices                                             | Key-value set of available choices
inline_editing.editor.view_options.placeholder      | Optional. Placeholder translation key for an empty element
inline_editing.editor.view_options.key_type         | Optional. Specifies type of value that should be sent to server. Currently string/boolean/number key types are supported.
inline_editing.editor.view_options.placeholder_raw  | Optional. Raw placeholder value
inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
inline_editing.validation_rules                     | Optional. Validation rules. See [documentation](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
inline_editing.save_api_accessor                    | Optional. Sets accessor module, route, parameters etc.

### Constructor parameters

**Extends:** <code>[TextEditorView](./text-editor-view.md)</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.model | <code>Object</code> | Current row model |
| options.fieldName | <code>string</code> | Field name to edit in model |
| options.className | <code>string</code> | CSS class name for editor element |
| options.placeholder | <code>string</code> | Placeholder translation key for an empty element |
| options.placeholder_raw | <code>string</code> | Raw placeholder value. It overrides placeholder translation key |
| options.validationRules | <code>Object</code> | Validation rules. See [documentation here](../reference/js_validation.md#conformity-server-side-validations-to-client-once) |
| options.choices | <code>Object</code> | Key-value set of available choices |


* [SelectEditorView](#module_SelectEditorView) ⇐ <code>[TextEditorView](./text-editor-view.md)</code>
  * [.getSelect2Options()](#module_SelectEditorView#getSelect2Options) ⇒ <code>Object</code>
  * [.getSelect2Data()](#module_SelectEditorView#getSelect2Data) ⇒ <code>Object</code>
  * [.onFocusout(e)](#module_SelectEditorView#onFocusout)
  * [.isFocused()](#module_SelectEditorView#isFocused) ⇒ <code>boolean</code>
  * [.getServerUpdateData()](#module_SelectEditorView#getServerUpdateData) ⇒ <code>Object</code>

<a name="module_SelectEditorView#getSelect2Options"></a>
### selectEditorView.getSelect2Options() ⇒ <code>Object</code>
Prepares and returns Select2 options

**Kind**: instance method of <code>[SelectEditorView](#module_SelectEditorView)</code>  
<a name="module_SelectEditorView#getSelect2Data"></a>
### selectEditorView.getSelect2Data() ⇒ <code>Object</code>
Returns Select2 data from corresponding element

**Kind**: instance method of <code>[SelectEditorView](#module_SelectEditorView)</code>  
<a name="module_SelectEditorView#onFocusout"></a>
### selectEditorView.onFocusout(e)
Handles focusout event

**Kind**: instance method of <code>[SelectEditorView](#module_SelectEditorView)</code>  

| Param | Type |
| --- | --- |
| e | <code>jQuery.Event</code> | 

<a name="module_SelectEditorView#isFocused"></a>
### selectEditorView.isFocused() ⇒ <code>boolean</code>
Returns true if element is focused

**Kind**: instance method of <code>[SelectEditorView](#module_SelectEditorView)</code>  
<a name="module_SelectEditorView#getServerUpdateData"></a>
### selectEditorView.getServerUpdateData() ⇒ <code>Object</code>
Returns data which should be sent to the server

**Kind**: instance method of <code>[SelectEditorView](#module_SelectEditorView)</code>  
