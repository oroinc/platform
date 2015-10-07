<a name="module_NumberEditorView"></a>
## NumberEditorView ⇐ <code>[TextEditorView](./text-editor-view.md)</code>
Number cell content editor.

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
        frontend_type: <number/integer/decimal/percent/currency>
      # Sample 2. Full configuration
      {column-name-2}:
        inline_editing:
          editor:
            view: orodatagrid/js/app/views/editor/number-editor-view
            view_options:
              placeholder: '<placeholder>'
          validationRules:
            # jQuery.validate configuration
            required: true
            min: 5
```

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:-----------
inline_editing.editor.view_options.placeholder      | Optional. Placeholder for empty element
inline_editing.editor.validationRules               | Optional. Client side validation rules

### Constructor parameters

**Extends:** <code>[TextEditorView](./text-editor-view.md)</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.model | <code>Object</code> | Current row model |
| options.cell | <code>Backgrid.Cell</code> | Current datagrid cell |
| options.column | <code>Backgrid.Column</code> | Current datagrid column |
| options.placeholder | <code>string</code> | Placeholder for empty element |
| options.validationRules | <code>Object</code> | Validation rules in form applicable to jQuery.validate |

