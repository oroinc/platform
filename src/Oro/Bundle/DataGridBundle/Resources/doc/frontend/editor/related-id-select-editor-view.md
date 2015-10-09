<a name="module_RelatedIdSelectEditorView"></a>
## RelatedIdSelectEditorView ⇐ <code>[SelectEditorView](./select-editor-view.md)</code>
Select-like cell content editor. This view is applicable when the cell value contains label (not the value).
The editor will use provided `choices` map and `value_field_name`. The server will be updated with value only.

### Column configuration sample:

Please pay attention to the registration of the `value_field_name` in `query` and `properties` sections of the
sample yml configuration below

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
              # choices: @choiceProvider->getAll
              choices: # required
                key-1: First
                key-2: Second
          validationRules:
            # jQuery.validate configuration
            required: true
    properties:
      # this line is required to add {column-name-value} to data sent to client
      {column-name-value}: ~
```

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:---------------------------------------
inline_editing.editor.view_options.choices          | Key-value set of available choices
inline_editing.editor.view_options.value_field_name | Related value field name.
inline_editing.editor.view_options.placeholder      | Optional. Placeholder for an empty element
inline_editing.editor.validationRules               | Optional. The client side validation rules


### Constructor parameters

**Extends:** <code>[SelectEditorView](./select-editor-view.md)</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.model | <code>Object</code> | Current row model |
| options.cell | <code>Backgrid.Cell</code> | Current datagrid cell |
| options.column | <code>Backgrid.Column</code> | Current datagrid column |
| options.placeholder | <code>string</code> | Placeholder for an empty element |
| options.validationRules | <code>Object</code> | Validation rules in a form applicable for jQuery.validate |
| options.choices | <code>Object</code> | Key-value set of available choices |
| options.value_field_name | <code>Object</code> | Related value field name |

