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
        frontend_type: <number/integer/decimal/currency>
      # Sample 2. Full configuration
      {column-name-2}:
        inline_editing:
          editor:
            view: orodatagrid/js/app/views/editor/number-editor-view
            view_options:
              placeholder: '<placeholder>'
              css_class_name: '<class-name>'
          validation_rules:
            # jQuery.validate configuration
            required: true
            min: 5
```

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:-----------
inline_editing.editor.view_options.placeholder      | Optional. Placeholder for an empty element
inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
inline_editing.editor.validation_rules               | Optional. The client side validation rules

### Constructor parameters

**Extends:** <code>[TextEditorView](./text-editor-view.md)</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.model | <code>Object</code> | Current row model |
| options.cell | <code>Backgrid.Cell</code> | Current datagrid cell |
| options.column | <code>Backgrid.Column</code> | Current datagrid column |
| options.placeholder | <code>string</code> | Placeholder for an empty element |
| options.validationRules | <code>Object</code> | Validation rules in a form applicable for jQuery.validate |

