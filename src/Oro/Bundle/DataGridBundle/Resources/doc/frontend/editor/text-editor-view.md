<a name="module_TextEditorView"></a>
## TextEditorView ⇐ <code>BaseView</code>
Text cell content editor. this view is used by default (when no frontend type specified).

Sample column configuration with full options provided:
``` yml
datagrid:
  {grid-uid}:
    # <grid configuration> goes here
    columns:
      {column-name}:
        inline_editing:
          editor:
            view: orodatagrid/js/app/views/editor/text-editor-view # Optional
            view_options:
              placeholder: '<placeholder>'
```

Available options:

Name                                           | Description
-----------------------------------------------|------------
inline_editing.editor.view_options.placeholder | Optional. Placeholder for empty element

**Extends:** <code>BaseView</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container. |
| options.model | <code>Object</code> | current row model |
| options.cell | <code>Backgrid.Cell</code> | current datagrid cell |
| options.column | <code>Backgrid.Column</code> | current datagrid column |
| options.placeholder | <code>string</code> | placeholder for empty element |
| options.validationRules | <code>Object</code> | validation rules in form applicable to jQuery.validate |


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
Places focus on editor

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type | Description |
| --- | --- | --- |
| atEnd | <code>boolean</code> | Usefull for multi inputs editors. Specifies which input should be focused first                         or last |

<a name="module_TextEditorView#getValidationRules"></a>
### textEditorView.getValidationRules() ⇒ <code>Object</code>
Prepares validation rules for usage

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#getFormattedValue"></a>
### textEditorView.getFormattedValue() ⇒ <code>string</code>
Formats and returns model value before it will be rendered

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#getModelValue"></a>
### textEditorView.getModelValue() ⇒ <code>string</code>
Returns raw model value

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#getValue"></a>
### textEditorView.getValue() ⇒ <code>string</code>
Returns current user edited value

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#rethrowAction"></a>
### textEditorView.rethrowAction() ⇒ <code>string</code>
Generic handler for buttons which allows to notify overlaying component about some user action.
Any button with 'data-action' attribute will rethrow action to inline editing plugin.

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

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#isChanged"></a>
### textEditorView.isChanged() ⇒ <code>boolean</code>
Returns true if user has changed value

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#onChange"></a>
### textEditorView.onChange()
Change handler. In this realization - tracks submit button disabled attribute

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#onKeyDown"></a>
### textEditorView.onKeyDown(e)
Keydown handler for entire document

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type |
| --- | --- |
| e | <code>$.Event</code> | 

<a name="module_TextEditorView#onGenericEnterKeydown"></a>
### textEditorView.onGenericEnterKeydown(e)
Generic keydown handler which handles ENTER

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type |
| --- | --- |
| e | <code>$.Event</code> | 

<a name="module_TextEditorView#onGenericTabKeydown"></a>
### textEditorView.onGenericTabKeydown(e)
Generic keydown handler which handles TAB

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type |
| --- | --- |
| e | <code>$.Event</code> | 

<a name="module_TextEditorView#onGenericEscapeKeydown"></a>
### textEditorView.onGenericEscapeKeydown(e)
Generic keydown handler which handles ESCAPE

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type |
| --- | --- |
| e | <code>$.Event</code> | 

<a name="module_TextEditorView#getServerUpdateData"></a>
### textEditorView.getServerUpdateData() ⇒ <code>Object</code>
Returns data which should be sent to server

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#getModelUpdateData"></a>
### textEditorView.getModelUpdateData() ⇒ <code>Object</code>
Returns data to update model

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
