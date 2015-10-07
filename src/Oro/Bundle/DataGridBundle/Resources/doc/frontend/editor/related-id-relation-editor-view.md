<a name="module_RelatedIdRelationEditorView"></a>
## RelatedIdRelationEditorView ⇐ <code>[SelectEditorView](./select-editor-view.md)</code>
/**
Select-like cell content editor. This view is applicable when cell value contains label (not the value).
Editor will use 'autocomplete_api_accessor' and `value_field_name`. Server will be updated with value only.

### Column configuration sample:

Please note the value_field_name registration in query and properties in the provided sample yml configuration

``` yml
datagrid:
  {grid-uid}:
    inline_editing:
      enable: true
    # <grid configuration> goes here
    source:
      query:
        select:
          # please note that both fields(value and label) are required for valid work
          - {entity}.id as {column-name-value}
          - {entity}.name as {column-name-label}
          # query continues here
    columns:
      {column-name-label}:
        inline_editing:
          editor:
            view: orodatagrid/js/app/views/editor/related-id-select-editor-view
            view_options:
              placeholder: '<placeholder>'
              value_field_name: {column-name-value}
          validationRules:
            # jQuery.validate configuration
            required: true
        autocomplete_api_accessor:
          # class: oroentity/js/tools/entity-select-search-api-accessor # entity_select is default search api
          entity_name: {corresponding-entity}
          field_name: {corresponding-entity-field-name}
    properties:
      # this line is required to add {column-name-value} to data sent to client
      {column-name-value}: ~
```

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:---------------------------------------
inline_editing.editor.view_options.value_field_name | Related value field name.
inline_editing.editor.view_options.placeholder      | Optional. Placeholder for empty element
inline_editing.editor.validationRules               | Optional. Client side validation rules
inline_editing.editor.autocomplete_api_accessor     | Required. Specifies available options
inline_editing.editor.autocomplete_api_accessor.class | One from the [list of search API's](../search-apis.md)

### Constructor parameters

**Extends:** <code>[SelectEditorView](./select-editor-view.md)</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.model | <code>Object</code> | Current row model |
| options.cell | <code>Backgrid.Cell</code> | Current datagrid cell |
| options.column | <code>Backgrid.Column</code> | Current datagrid column |
| options.placeholder | <code>string</code> | Placeholder for empty element |
| options.validationRules | <code>Object</code> | Validation rules in form applicable to jQuery.validate |
| options.value_field_name | <code>Object</code> | Related value field name |
| options.autocomplete_api_accessor | <code>Object</code> | Autocomplete API specification.                                      Please see [list of search API's](../search-apis.md) |

