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
              css_class_name: '<class-name>'
              value_field_name: {column-name-value}
              # choices: @choiceProvider->getAll
              choices: # required
                key-1: First
                key-2: Second
          validation_rules:
            NotBlank: ~
    properties:
      # this line is required to add {column-name-value} to data sent to client
      {column-name-value}: ~
```

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:---------------------------------------
inline_editing.editor.view_options.choices          | Key-value set of available choices
inline_editing.editor.view_options.value_field_name | Related value field name.
inline_editing.editor.view_options.placeholder      | Optional. Placeholder translation key for an empty element
inline_editing.editor.view_options.placeholder_raw  | Optional. Raw placeholder value
inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
inline_editing.editor.validation_rules | Optional. Validation rules. See [documentation](https://goo.gl/j9dj4Y)


### Constructor parameters

**Extends:** <code>[SelectEditorView](./select-editor-view.md)</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.model | <code>Object</code> | Current row model |
| options.cell | <code>Backgrid.Cell</code> | Current datagrid cell |
| options.column | <code>Backgrid.Column</code> | Current datagrid column |
| options.placeholder | <code>string</code> | Placeholder translation key for an empty element |
| options.placeholder_raw | <code>string</code> | Raw placeholder value. It overrides placeholder translation key |
| options.validationRules | <code>Object</code> | Validation rules. See [documentation here](https://goo.gl/j9dj4Y) |
| options.choices | <code>Object</code> | Key-value set of available choices |
| options.value_field_name | <code>Object</code> | Related value field name |

