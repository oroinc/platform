## RelatedIdSelectEditorView ⇐ [SelectEditorView](./select-editor-view.md)

<a name="module_RelatedIdSelectEditorView"></a>

Select-like cell content editor. This view is applicable when the cell value contains label (not the value).
The editor will use provided `choices` map and `value_field_name`. The server will be updated with value only.

### Column configuration sample:

Please pay attention to the registration of the `value_field_name` in `query` and `properties` sections of the
sample yml configuration below

``` yml
datagrids:
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
            view: oroform/js/app/views/editor/related-id-select-editor-view
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
          save_api_accessor:
              route: '<route>'
              query_parameter_names:
                 - '<parameter1>'
                 - '<parameter2>'
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
inline_editing.validation_rules | Optional. Validation rules. See [documentation](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
inline_editing.save_api_accessor                    | Optional. Sets accessor module, route, parameters etc.


### Constructor parameters

**Extends:** [SelectEditorView](./select-editor-view.md)  

| Param | Type | Description |
| --- | --- | --- |
| options | `Object` | Options container |
| options.model | `Object` | Current row model |
| options.className | `string` | CSS class name for editor element |
| options.fieldName | `string` | Field name to edit in model |
| options.placeholder | `string` | Placeholder translation key for an empty element |
| options.placeholder_raw | `string` | Raw placeholder value. It overrides placeholder translation key |
| options.validationRules | `Object` | Validation rules. See [documentation here](../reference/js_validation.md#conformity-server-side-validations-to-client-once) |
| options.choices | `Object` | Key-value set of available choices |
| options.value_field_name | `Object` | Related value field name |

