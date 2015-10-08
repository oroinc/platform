<a name="module_TextEditorView"></a>
## TextEditorView ⇐ <code>BaseView</code>
Text cell content editor. This view is used by default (if no frontend type has been specified).

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
        frontend_type: string
      # Sample 2. Mapped by frontend type and placeholder specified
      {column-name-2}:
        frontend_type: string
        inline_editing:
          editor:
            view_options:
              placeholder: '<placeholder>'
      # Sample 3. Full configuration
      {column-name-3}:
        inline_editing:
          editor:
            view: orodatagrid/js/app/views/editor/text-editor-view
            view_options:
              placeholder: '<placeholder>'
          validationRules:
            # jQuery.validate configuration
            required: true
            minlen: 5
```

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:-----------
inline_editing.editor.view_options.placeholder      | Optional. Placeholder for an empty element
inline_editing.editor.validationRules               | Optional. Client side validation rules

### Constructor parameters

**Extends:** <code>BaseView</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.model | <code>Object</code> | Current row model |
| options.cell | <code>Backgrid.Cell</code> | Current datagrid cell |
| options.column | <code>Backgrid.Column</code> | Current datagrid column |
| options.placeholder | <code>string</code> | Placeholder for an empty element |
| options.validationRules | <code>Object</code> | Validation rules in a form applicable for jQuery.validate |


* [TextEditorView](#module_TextEditorView) ⇐ <code>BaseView</code>
  * [.focus(atEnd)](#module_TextEditorView#focus)
  * [.getValidationRules()](#module_TextEditorView#getValidationRules) ⇒ <code>Object</code>
  * [.getFormattedValue()](#module_TextEditorView#getFormattedValue) ⇒ <code>string</code>
  * [.getModelValue()](#module_TextEditorView#getModelValue) ⇒ <code>string</code>
  * [.getValue()](#module_TextEditorView#getValue) ⇒ <code>string</code>
  * [.rethrowAction()](#module_TextEditorView#rethrowAction) ⇒ <code>string</code>
  * [.isChanged()](#module_TextEditorView#isChanged) ⇒ <code>boolean</code>
  * [.onChange()](#module_TextEditorView#onChange)
  * [.onKeyDown(e)](#module_TextEditorView#onKeyDown)
  * [.onGenericEnterKeydown(e)](#module_TextEditorView#onGenericEnterKeydown)
  * [.onGenericTabKeydown(e)](#module_TextEditorView#onGenericTabKeydown)
  * [.onGenericEscapeKeydown(e)](#module_TextEditorView#onGenericEscapeKeydown)
  * [.getServerUpdateData()](#module_TextEditorView#getServerUpdateData) ⇒ <code>Object</code>
  * [.getModelUpdateData()](#module_TextEditorView#getModelUpdateData) ⇒ <code>Object</code>

<a name="module_TextEditorView#focus"></a>
### textEditorView.focus(atEnd)
Places focus on the editor

**Kind**: an instance method of the <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type | Description |
| --- | --- | --- |
| atEnd | <code>boolean</code> | Usefull for multi input editors. Specifies which input should be processed first                         or last |

<a name="module_TextEditorView#getValidationRules"></a>
### textEditorView.getValidationRules() ⇒ <code>Object</code>
Prepares validation rules for usage

**Kind**: an instance method of the <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#getFormattedValue"></a>
### textEditorView.getFormattedValue() ⇒ <code>string</code>
Formats and returns the model value before it is rendered

**Kind**: an instance method of the <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#getModelValue"></a>
### textEditorView.getModelValue() ⇒ <code>string</code>
Returns a raw model value

**Kind**: an instance method of the <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#getValue"></a>
### textEditorView.getValue() ⇒ <code>string</code>
Returns the current user edited value

**Kind**: an instance method of the <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#rethrowAction"></a>
### textEditorView.rethrowAction() ⇒ <code>string</code>
Generic handler for buttons which allows to notify overlaying component about some user action.
Any button with 'data-action' attribute will rethrow the action to the inline editing plugin.

Available actions:
- save
- cancel
- saveAndEditNext
- saveAndEditPrev
- cancelAndEditNext
- cancelAndEditPrev

Sample usage:
``` html
 <button data-action="cancelAndEditNext">Skip and Go Next</button>
```

**Kind**: an instance method of the <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#isChanged"></a>
### textEditorView.isChanged() ⇒ <code>boolean</code>
Returns true if the user has changed the value

**Kind**: an instance method of the <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#onChange"></a>
### textEditorView.onChange()
Change handler. In this realization, it tracks a submit button disabled attribute

**Kind**: an instance method of the <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#onKeyDown"></a>
### textEditorView.onKeyDown(e)
Keydown handler for the entire document

**Kind**: an instance method of the <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type |
| --- | --- |
| e | <code>$.Event</code> | 

<a name="module_TextEditorView#onGenericEnterKeydown"></a>
### textEditorView.onGenericEnterKeydown(e)
Generic keydown handler, which handles ENTER

**Kind**: an instance method of the <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type |
| --- | --- |
| e | <code>$.Event</code> | 

<a name="module_TextEditorView#onGenericTabKeydown"></a>
### textEditorView.onGenericTabKeydown(e)
Generic keydown handler, which handles TAB

**Kind**: an instance method of the <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type |
| --- | --- |
| e | <code>$.Event</code> | 

<a name="module_TextEditorView#onGenericEscapeKeydown"></a>
### textEditorView.onGenericEscapeKeydown(e)
Generic keydown handler, which handles ESCAPE

**Kind**: an instance method of the <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type |
| --- | --- |
| e | <code>$.Event</code> | 

<a name="module_TextEditorView#getServerUpdateData"></a>
### textEditorView.getServerUpdateData() ⇒ <code>Object</code>
Returns data which should be sent to the server

**Kind**: an instance method of the <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#getModelUpdateData"></a>
### textEditorView.getModelUpdateData() ⇒ <code>Object</code>
Returns data to the update model

**Kind**: an instance method of the <code>[TextEditorView](#module_TextEditorView)</code>  
