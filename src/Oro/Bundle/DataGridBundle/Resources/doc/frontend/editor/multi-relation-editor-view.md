<a name="module_MultiRelationEditorView"></a>
## MultiRelationEditorView ⇐ <code>[RelatedIdRelationEditorView](./related-id-relation-editor-view.md)</code>
Multi-relation content editor. Please note that it requires column data format
corresponding to multi-relation-cell.

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
            view: oroform/js/app/views/editor/multi-relation-editor-view
            view_options:
              placeholder: '<placeholder>'
              css_class_name: '<class-name>'
              maximumSelectionLength: 3
          validation_rules:
            NotBlank: true
        autocomplete_api_accessor:
          # class: oroentity/js/tools/entity-select-search-api-accessor
          # entity_select is default search api
          # following options are specific only for entity-select-search-api-accessor
          # please place here an options corresponding to specified class
          entity_name: {corresponding-entity}
          field_name: {corresponding-entity-field-name}
```

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:-----------
inline_editing.editor.view_options.placeholder      | Optional. Placeholder translation key for an empty element
inline_editing.editor.view_options.placeholder_raw  | Optional. Raw placeholder value
inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
inline_editing.editor.view_options.maximumSelectionLength | Optional. Maximum selection length
inline_editing.editor.validation_rules | Optional. Validation rules. See [documentation](https://goo.gl/j9dj4Y)
inline_editing.editor.autocomplete_api_accessor     | Required. Specifies available choices
inline_editing.editor.autocomplete_api_accessor.class | One of the [list of search APIs](../search-apis.md)

### Constructor parameters

**Extends:** <code>[RelatedIdRelationEditorView](./related-id-relation-editor-view.md)</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.model | <code>Object</code> | Current row model |
| options.cell | <code>Backgrid.Cell</code> | Current datagrid cell |
| options.column | <code>Backgrid.Column</code> | Current datagrid column |
| options.placeholder | <code>string</code> | Placeholder translation key for an empty element |
| options.placeholder_raw | <code>string</code> | Raw placeholder value. It overrides placeholder translation key |
| options.maximumSelectionLength | <code>string</code> | Maximum selection length |
| options.validationRules | <code>Object</code> | Validation rules. See [documentation here](https://goo.gl/j9dj4Y) |
| options.autocomplete_api_accessor | <code>Object</code> | Autocomplete API specification.                                      Please see [list of search API's](../search-apis.md) |

