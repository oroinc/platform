## AbstractRelationEditorView ⇐ [SelectEditorView](./select-editor-view.md)

<a name="module_AbstractRelationEditorView"></a>
Abstract select editor which requests data from server.

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:---------------------------------------
inline_editing.editor.view_options.placeholder      | Optional. Placeholder for an empty element
inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
inline_editing.editor.view_options.input_delay      | Delay before user finished input and request sent to server
inline_editing.autocomplete_api_accessor     | Required. Specifies available choices
inline_editing.autocomplete_api_accessor.class | One of the [list of search APIs](../reference/search-apis.md)

### Constructor parameters

**Extends:** [SelectEditorView](./select-editor-view.md) 

| Param | Type | Description |
| --- | --- | --- |
| options | `Object` | Options container |
| options.model | `Object` | Current row model |
| options.input_delay | `Object` | Delay before user finished input and request sent to server |
| options.fieldName | `string` | Field name to edit in model |
| options.placeholder | `string` | Placeholder for an empty element |
| options.validationRules | `Object` | Validation rules. See [documentation here](../reference/js_validation.md#conformity-server-side-validations-to-client-once) |
| options.autocomplete_api_accessor | `Object` | Autocomplete API specification.                                      Please see [list of search API's](../reference/search-apis.md) |

