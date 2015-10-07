<a name="module_DateEditorView"></a>
## DateEditorView ⇐ <code>(TextEditorView)[./text-editor-view.md]</code>
Date cell content editor

### Column configuration samples:
``` yml
datagrid:
  {grid-uid}:
    inline_editing:
      enable: true
    # <grid configuration> goes here
    columns:
      # Sample 1. Mapped by number frontend type
      {column-name-1}:
        frontend_type: date
      # Sample 2. Full configuration
      {column-name-2}:
        inline_editing:
          editor:
            view: orodatagrid/js/app/views/editor/date-editor-view
            view_options:
              placeholder: '<placeholder>'
              datePickerOptions:
                altFormat: 'yy-mm-dd'
                changeMonth: true
                changeYear: true
                yearRange: '-80:+1'
                showButtonPanel: true
          validationRules:
            # jQuery.validate configuration
            required: true
```

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:-----------
inline_editing.editor.view_options.placeholder      | Optional. Placeholder for empty element
inline_editing.editor.view_options.dateInputAttrs   | Optional. Attributes for date HTML input element
inline_editing.editor.view_options.datePickerOptions| Optional. See (details here)[http://goo.gl/pddxZU]
inline_editing.editor.validationRules               | Optional. Client side validation rules

### Constructor parameters

**Extends:** <code>(TextEditorView)[./text-editor-view.md]</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.model | <code>Object</code> | Current row model |
| options.cell | <code>Backgrid.Cell</code> | Current datagrid cell |
| options.column | <code>Backgrid.Column</code> | Current datagrid column |
| options.placeholder | <code>string</code> | Placeholder for empty element |
| options.validationRules | <code>Object</code> | Validation rules in form applicable to jQuery.validate |

<a name="module_DateEditorView#render"></a>
### dateEditorView.render()
**Kind**: instance method of <code>[DateEditorView](#module_DateEditorView)</code>  
