<a name="module_PercentEditorView"></a>
## PercentEditorView ⇐ <code>[NumberEditorView](./number-editor-view.md)</code>
Percent cell content editor.

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
        frontend_type: percent
      # Sample 2. Full configuration
      {column-name-2}:
        inline_editing:
          editor:
            view: orodatagrid/js/app/views/editor/percent-editor-view
            view_options:
              placeholder: '<placeholder>'
              css_class_name: '<class-name>'
          validation_rules:
            NotBlank: ~
```

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:-----------
inline_editing.editor.view_options.placeholder      | Optional. Placeholder translation key for an empty element
inline_editing.editor.view_options.placeholder_raw  | Optional. Raw placeholder value
inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
inline_editing.editor.validation_rules | Optional. Validation rules. See [documentation](https://goo.gl/j9dj4Y)

### Constructor parameters

**Extends:** <code>[NumberEditorView](./number-editor-view.md)</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.model | <code>Object</code> | Current row model |
| options.cell | <code>Backgrid.Cell</code> | Current datagrid cell |
| options.column | <code>Backgrid.Column</code> | Current datagrid column |
| options.placeholder | <code>string</code> | Placeholder translation key for an empty element |
| options.placeholder_raw | <code>string</code> | Raw placeholder value. It overrides placeholder translation key |
| options.validationRules | <code>Object</code> | Validation rules. See [documentation here](https://goo.gl/j9dj4Y) |

