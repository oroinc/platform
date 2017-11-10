## MultiCheckboxEditorView ⇐ [SelectEditorView](./select-editor-view.md)
<a name="module_MultiCheckboxEditorView"></a>
Multi-select content editor. Please note that it requires column data format
corresponding to multi-select-cell.

### Column configuration samples:
``` yml
datagrids:
  {grid-uid}:
    inline_editing:
      enable: true
    # <grid configuration> goes here
    columns:
      # Sample 1. Full configuration
      {column-name-1}:
        inline_editing:
          editor:
            view: oroform/js/app/views/editor/multi-checkbox-editor-view
            view_options:
              css_class_name: '<class-name>'
          validation_rules:
            NotBlank: true
          save_api_accessor:
              route: '<route>'
              query_parameter_names:
                 - '<parameter1>'
                 - '<parameter2>'
```

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:-----------
inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
inline_editing.editor.validation_rules | Optional. Validation rules. See [documentation](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
inline_editing.save_api_accessor                    | Optional. Sets accessor module, route, parameters etc.

### Constructor parameters

**Extends:** [SelectEditorView](./select-editor-view.md)  

| Param | Type | Description |
| --- | --- | --- |
| options | `Object` | Options container |
| options.model | `Object` | Current row model |
| options.placeholder | `string` | Placeholder translation key for an empty element |
| options.placeholder_raw | `string` | Raw placeholder value. It overrides placeholder translation key |
| options.maximumSelectionLength | `string` | Maximum selection length |
| options.validationRules | `Object` | Validation rules. See [documentation here](../reference/js_validation.md#conformity-server-side-validations-to-client-once) |
| options.value | `string` | initial value of edited field |

<a name="module_MultiCheckboxEditorView#multiselect"></a>
### multiCheckboxEditorView.multiselect
Jquery object that wraps select DOM element with initialized multiselect plugin

**Kind**: instance property of [MultiCheckboxEditorView](#module_MultiCheckboxEditorView)  
**Properties**

| Type |
| --- |
| `Object` | 

