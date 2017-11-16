## MultiRelationEditorView ⇐ [RelatedIdRelationEditorView](./related-id-relation-editor-view.md)

<a name="module_MultiRelationEditorView"></a>

Multi-relation content editor. Please note that it requires column data format corresponding to multi-relation-cell.

### Column configuration samples:
``` yml
datagrids:
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
inline_editing.validation_rules | Optional. Validation rules. See [documentation](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
inline_editing.autocomplete_api_accessor     | Required. Specifies available choices
inline_editing.autocomplete_api_accessor.class | One of the [list of search APIs](../reference/search-apis.md)

### Constructor parameters

**Extends:** [RelatedIdRelationEditorView](./related-id-relation-editor-view.md)  

| Param | Type | Description |
| --- | --- | --- |
| options | `Object` | Options container |
| options.model | `Object` | Current row model |
| options.cell | `Backgrid.Cell` | Current datagrid cell |
| options.column | `Backgrid.Column` | Current datagrid column |
| options.placeholder | `string` | Placeholder translation key for an empty element |
| options.placeholder_raw | `string` | Raw placeholder value. It overrides placeholder translation key |
| options.maximumSelectionLength | `string` | Maximum selection length |
| options.validationRules | `Object` | Validation rules. See [documentation here](../reference/js_validation.md#conformity-server-side-validations-to-client-once) |
| options.autocomplete_api_accessor | `Object` | Autocomplete API specification. Please see [list of search API's](../reference/search-apis.md) |

