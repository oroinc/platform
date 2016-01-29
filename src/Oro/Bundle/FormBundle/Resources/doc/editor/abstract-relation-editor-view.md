<a name="module_AbstractRelationEditorView"></a>
## AbstractRelationEditorView ⇐ <code>[SelectEditorView](./select-editor-view.md)</code>
Abstract select editor which requests data from server.

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:---------------------------------------
inline_editing.editor.view_options.placeholder      | Optional. Placeholder for an empty element
inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
inline_editing.editor.view_options.input_delay      | Delay before user finished input and request sent to server
inline_editing.editor.autocomplete_api_accessor     | Required. Specifies available choices
inline_editing.editor.autocomplete_api_accessor.class | One of the [list of search APIs](../reference/search-apis.md)

### Constructor parameters

**Extends:** <code>[SelectEditorView](./select-editor-view.md)</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.model | <code>Object</code> | Current row model |
| options.input_delay | <code>Object</code> | Delay before user finished input and request sent to server |
| options.fieldName | <code>string</code> | Field name to edit in model |
| options.placeholder | <code>string</code> | Placeholder for an empty element |
| options.validationRules | <code>Object</code> | Validation rules. See [documentation here](https://goo.gl/j9dj4Y) |
| options.autocomplete_api_accessor | <code>Object</code> | Autocomplete API specification.                                      Please see [list of search API's](../reference/search-apis.md) |

