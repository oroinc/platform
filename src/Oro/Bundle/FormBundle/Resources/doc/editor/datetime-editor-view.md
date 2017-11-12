<a name="module_DatetimeEditorView"></a>
## DatetimeEditorView ⇐ [DateEditorView](./date-editor-view.md)
Datetime cell content editor

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
        frontend_type: datetime
      # Sample 2. Full configuration
      {column-name-2}:
        inline_editing:
          editor:
            view: oroform/js/app/views/editor/datetime-editor-view
            view_options:
              css_class_name: '<class-name>'
              datePickerOptions:
                # See http://api.jqueryui.com/datepicker/
                altFormat: 'yy-mm-dd'
                changeMonth: true
                changeYear: true
                yearRange: '-80:+1'
                showButtonPanel: true
              timePickerOptions:
                # See https://github.com/jonthornton/jquery-timepicker#options
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
:---------------------------------------------------|:-----------
inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
inline_editing.editor.view_options.dateInputAttrs   | Optional. Attributes for the date HTML input element
inline_editing.editor.view_options.datePickerOptions| Optional. See [documentation here](http://api.jqueryui.com/datepicker/)
inline_editing.editor.view_options.timeInputAttrs   | Optional. Attributes for the time HTML input element
inline_editing.editor.view_options.timePickerOptions| Optional. See [documentation here](https://github.com/jonthornton/jquery-timepicker#options)
inline_editing.validation_rules | Optional. Validation rules. See [documentation](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
inline_editing.save_api_accessor                    | Optional. Sets accessor module, route, parameters etc.

### Constructor parameters

**Extends:** [DateEditorView](./date-editor-view.md)  

| Param | Type | Description |
| --- | --- | --- |
| options | `Object` | Options container |
| options.model | `Object` | Current row model |
| options.fieldName | `string` | Field name to edit in model |
| options.validationRules | `Object` | Validation rules. See [documentation here](../reference/js_validation.md#conformity-server-side-validations-to-client-once) |
| options.dateInputAttrs | `Object` | Attributes for date HTML input element |
| options.datePickerOptions | `Object` | See [documentation here](http://api.jqueryui.com/datepicker/) |
| options.timeInputAttrs | `Object` | Attributes for time HTML input element |
| options.timePickerOptions | `Object` | See [documentation here](https://github.com/jonthornton/jquery-timepicker#options) |
| options.value | `string` | initial value of edited field |

