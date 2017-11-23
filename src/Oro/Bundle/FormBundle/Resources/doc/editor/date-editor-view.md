## DateEditorView ⇐ [TextEditorView](./text-editor-view.md)

<a name="module_DateEditorView"></a>
Date cell content editor.

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
        frontend_type: date
      # Sample 2. Full configuration
      {column-name-2}:
        inline_editing:
          editor:
            view: oroform/js/app/views/editor/date-editor-view
            view_options:
              css_class_name: '<class-name>'
              datePickerOptions:
                altFormat: 'yy-mm-dd'
                changeMonth: true
                changeYear: true
                yearRange: '-80:+1'
                showButtonPanel: true
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
inline_editing.editor.view_options.dateInputAttrs   | Optional. Attributes for the date HTML input element
inline_editing.editor.view_options.datePickerOptions| Optional. See [documentation here](http://api.jqueryui.com/datepicker/)
inline_editing.validation_rules | Optional. Validation rules. See [documentation](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
inline_editing.save_api_accessor                    | Optional. Sets accessor module, route, parameters etc.

### Constructor parameters

**Extends:** [TextEditorView](./text-editor-view.md)  

| Param | Type | Description |
| --- | --- | --- |
| options | `Object` | Options container |
| options.model | `Object` | Current row model |
| options.fieldName | `string` | Field name to edit in model |
| options.validationRules | `Object` | Validation rules. See [documentation here](../reference/js_validation.md#conformity-server-side-validations-to-client-once) |
| options.dateInputAttrs | `Object` | Attributes for date HTML input element |
| options.datePickerOptions | `Object` | See [documentation here](http://api.jqueryui.com/datepicker/) |
| options.value | `string` | initial value of edited field |

<a name="module_DateEditorView#getViewOptions"></a>
### dateEditorView.getViewOptions() ⇒ `Object`
Prepares and returns editor sub-view options

**Kind**: instance method of [DateEditorView](#module_DateEditorView)  
