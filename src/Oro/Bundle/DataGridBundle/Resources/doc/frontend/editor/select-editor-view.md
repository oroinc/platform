<a name="module_SelectEditorView"></a>
## SelectEditorView ⇐ <code>[TextEditorView](./text-editor-view.md)</code>
Select cell content editor. The cell value should be a value field.
The grid will render a corresponding label from the options.choices map.
The editor will use the same mapping

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
        frontend_type: select
        choices: # required
          key-1: First
          key-2: Second
      # Sample 2. Full configuration
      {column-name-2}:
        choices: # required
          key-1: First
          key-2: Second
        inline_editing:
          editor:
            view: orodatagrid/js/app/views/editor/select-editor-view
            view_options:
              placeholder: '<placeholder>'
          validationRules:
            # jQuery.validate configuration
            required: true
```

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:---------------------------------------
choices                                             | Key-value set of available choices
inline_editing.editor.view_options.placeholder      | Optional. Placeholder for an empty element
inline_editing.editor.validationRules               | Optional. The client side validation rules

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
| options.choices | <code>Object</code> | Key-value set of available choices |

<a name="module_SelectEditorView#getSelect2Options"></a>
### selectEditorView.getSelect2Options() ⇒ <code>Object</code>
Prepares and returns Select2 options

**Kind**: an instance method of the <code>[SelectEditorView](#module_SelectEditorView)</code>  
