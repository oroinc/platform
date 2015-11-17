<a name="module_MultiSelectEditorView"></a>
## MultiSelectEditorView ⇐ <code>[RelatedIdRelationEditorView](./related-id-relation-editor-view.md)</code>
Multi-select content editor. Please note that it requires column data format
corresponding to multi-select-cell.

### Column configuration samples:
``` yml
datagrid:
  {grid-uid}:
    inline_editing:
      enable: true
    # <grid configuration> goes here
    columns:
      # Sample 1. Full configuration
      {column-name-1}:
        inline_editing:
          editor:
            view: orodatagrid/js/app/views/editor/multi-relation-editor-view
            view_options:
              placeholder: '<placeholder>'
              css_class_name: '<class-name>'
              maximumSelectionLength: 3
          validation_rules:
            NotBlank: true
```

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:-----------
inline_editing.editor.view_options.placeholder      | Optional. Placeholder for an empty element
inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
inline_editing.editor.view_options.maximumSelectionLength | Optional. Maximum selection length
inline_editing.editor.validation_rules | Optional. Validation rules. See [documentation](https://goo.gl/j9dj4Y)

### Constructor parameters

**Extends:** <code>[RelatedIdRelationEditorView](./related-id-relation-editor-view.md)</code>, <code>[RelatedIdRelationEditorView](./related-id-relation-editor-view.md)</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.model | <code>Object</code> | Current row model |
| options.cell | <code>Backgrid.Cell</code> | Current datagrid cell |
| options.column | <code>Backgrid.Column</code> | Current datagrid column |
| options.placeholder | <code>string</code> | Placeholder for an empty element |
| options.maximumSelectionLength | <code>string</code> | Maximum selection length |
| options.validationRules | <code>Object</code> | Validation rules. See [documentation here](https://goo.gl/j9dj4Y) |

