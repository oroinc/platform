<a name="module_DatetimeEditorView"></a>
## DatetimeEditorView ⇐ <code>[DateEditorView](./date-editor-view.md)</code>
Datetime cell content editor

### Column configuration samples:
``` yml
datagrid:
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
            view: orodatagrid/js/app/views/editor/date-editor-view
            view_options:
              placeholder: '<placeholder>'
              datePickerOptions:
                # See http://goo.gl/pddxZU
                altFormat: 'yy-mm-dd'
                changeMonth: true
                changeYear: true
                yearRange: '-80:+1'
                showButtonPanel: true
              timePickerOptions:
                # See https://github.com/jonthornton/jquery-timepicker#options
          validationRules:
            # jQuery.validate configuration
            required: true
```

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:-----------
inline_editing.editor.view_options.placeholder      | Optional. Placeholder for empty element
inline_editing.editor.view_options.dateInputAttrs   | Optional. Attributes for date HTML input element
inline_editing.editor.view_options.datePickerOptions| Optional. See [documentation here](http://goo.gl/pddxZU)
inline_editing.editor.view_options.timeInputAttrs   | Optional. Attributes for time HTML input element
inline_editing.editor.view_options.timePickerOptions| Optional. See [documentation here](https://goo.gl/MP6Unb)
inline_editing.editor.validationRules               | Optional. Client side validation rules

### Constructor parameters

**Extends:** <code>[DateEditorView](./date-editor-view.md)</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.model | <code>Object</code> | Current row model |
| options.cell | <code>Backgrid.Cell</code> | Current datagrid cell |
| options.column | <code>Backgrid.Column</code> | Current datagrid column |
| options.placeholder | <code>string</code> | Placeholder for empty element |
| options.validationRules | <code>Object</code> | Validation rules in form applicable to jQuery.validate |
| options.dateInputAttrs | <code>Object</code> | Attributes for date HTML input element |
| options.datePickerOptions | <code>Object</code> | See [documentation here](http://goo.gl/pddxZU) |
| options.timeInputAttrs | <code>Object</code> | Attributes for time HTML input element |
| options.timePickerOptions | <code>Object</code> | See [documentation here](https://goo.gl/MP6Unb) |

