<a name="module_DatetimeEditorView"></a>
## DatetimeEditorView ⇐ <code>[DateEditorView](./date-editor-view.md)</code>
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

**Extends:** <code>[DateEditorView](./date-editor-view.md)</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.model | <code>Object</code> | Current row model |
| options.fieldName | <code>string</code> | Field name to edit in model |
| options.validationRules | <code>Object</code> | Validation rules. See [documentation here](../reference/js_validation.md#conformity-server-side-validations-to-client-once) |
| options.dateInputAttrs | <code>Object</code> | Attributes for date HTML input element |
| options.datePickerOptions | <code>Object</code> | See [documentation here](http://api.jqueryui.com/datepicker/) |
| options.timeInputAttrs | <code>Object</code> | Attributes for time HTML input element |
| options.timePickerOptions | <code>Object</code> | See [documentation here](https://github.com/jonthornton/jquery-timepicker#options) |
| options.value | <code>string</code> | initial value of edited field |

