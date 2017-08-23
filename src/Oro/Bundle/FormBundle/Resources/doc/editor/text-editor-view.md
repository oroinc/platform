<a name="module_TextEditorView"></a>
## TextEditorView ⇐ <code>BaseView</code>
Text cell content editor. This view is used by default (if no frontend type has been specified).

### Column configuration samples:
``` yml
datagrids:
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
            view: oroform/js/app/views/editor/text-editor-view
            view_options:
              placeholder: '<placeholder>'
              css_class_name: '<class-name>'
          validation_rules:
            NotBlank: ~
            Length:
              min: 3
              max: 255
          save_api_accessor:
              route: '<route>'
              query_parameter_names:
                 - '<parameter1>'
                 - '<parameter2>'
```

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:-----------
inline_editing.editor.view_options.placeholder      | Optional. Placeholder translation key for an empty element
inline_editing.editor.view_options.placeholder_raw  | Optional. Raw placeholder value
inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
inline_editing.validation_rules | Optional. Validation rules. See [documentation](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
inline_editing.save_api_accessor                    | Optional. Sets accessor module, route, parameters etc.

### Constructor parameters

**Extends:** <code>BaseView</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.model | <code>Object</code> | Current row model |
| options.className | <code>string</code> | CSS class name for editor element |
| options.fieldName | <code>string</code> | Field name to edit in model |
| options.placeholder | <code>string</code> | Placeholder translation key for an empty element |
| options.placeholder_raw | <code>string</code> | Raw placeholder value. It overrides placeholder translation key |
| options.validationRules | <code>Object</code> | Validation rules. See [documentation here](../reference/js_validation.md#conformity-server-side-validations-to-client-once) |
| options.value | <code>string</code> | initial value of edited field |


* [TextEditorView](#module_TextEditorView) ⇐ <code>BaseView</code>
  * [.ARROW_LEFT_KEY_CODE](#module_TextEditorView#ARROW_LEFT_KEY_CODE)
  * [._isFocused](#module_TextEditorView#_isFocused)
  * [.getPlaceholder()](#module_TextEditorView#getPlaceholder) ⇒ <code>string</code>
  * [.showBackendErrors(backendErrors)](#module_TextEditorView#showBackendErrors)
  * [.getFormState()](#module_TextEditorView#getFormState) ⇒ <code>Object</code>
  * [.setFormState(value)](#module_TextEditorView#setFormState)
  * [.focus(atEnd)](#module_TextEditorView#focus)
  * [.onFocusin(e)](#module_TextEditorView#onFocusin)
  * [.onFocusout(e)](#module_TextEditorView#onFocusout)
  * [.onMousedown(e)](#module_TextEditorView#onMousedown)
  * [.blur()](#module_TextEditorView#blur)
  * [.getValidationRules()](#module_TextEditorView#getValidationRules) ⇒ <code>Object</code>
  * [.getRawModelValue()](#module_TextEditorView#getRawModelValue) ⇒ <code>\*</code>
  * [.formatRawValue(value)](#module_TextEditorView#formatRawValue) ⇒ <code>string</code>
  * [.parseRawValue(value)](#module_TextEditorView#parseRawValue) ⇒ <code>\*</code>
  * [.getModelValue()](#module_TextEditorView#getModelValue) ⇒ <code>string</code>
  * [.getValue()](#module_TextEditorView#getValue) ⇒ <code>string</code>
  * [.rethrowAction()](#module_TextEditorView#rethrowAction) ⇒ <code>string</code>
  * [.rethrowEvent()](#module_TextEditorView#rethrowEvent)
  * [.isChanged()](#module_TextEditorView#isChanged) ⇒ <code>boolean</code>
  * [.isValid()](#module_TextEditorView#isValid) ⇒ <code>boolean</code>
  * [.updateSubmitButtonState()](#module_TextEditorView#updateSubmitButtonState)
  * [.onGenericKeydown(e)](#module_TextEditorView#onGenericKeydown)
  * [.onGenericEnterKeydown(e)](#module_TextEditorView#onGenericEnterKeydown)
  * [.onGenericTabKeydown(e)](#module_TextEditorView#onGenericTabKeydown)
  * [.onGenericEscapeKeydown(e)](#module_TextEditorView#onGenericEscapeKeydown)
  * [.onGenericArrowKeydown(e)](#module_TextEditorView#onGenericArrowKeydown)
  * [.getServerUpdateData()](#module_TextEditorView#getServerUpdateData) ⇒ <code>Object</code>
  * [.getModelUpdateData()](#module_TextEditorView#getModelUpdateData) ⇒ <code>Object</code>

<a name="module_TextEditorView#ARROW_LEFT_KEY_CODE"></a>
### textEditorView.ARROW_LEFT_KEY_CODE
Arrow codes

**Kind**: instance property of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#_isFocused"></a>
### textEditorView._isFocused
Internal focus tracking variable

**Kind**: instance property of <code>[TextEditorView](#module_TextEditorView)</code>  
**Access:** protected  
<a name="module_TextEditorView#getPlaceholder"></a>
### textEditorView.getPlaceholder() ⇒ <code>string</code>
Returns placeholder

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#showBackendErrors"></a>
### textEditorView.showBackendErrors(backendErrors)
Shows backend validation errors

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type | Description |
| --- | --- | --- |
| backendErrors | <code>Object</code> | map of field name to its error |

<a name="module_TextEditorView#getFormState"></a>
### textEditorView.getFormState() ⇒ <code>Object</code>
Reads state of form (map of element name to its value)

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#setFormState"></a>
### textEditorView.setFormState(value)
Set values to form elements

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type | Description |
| --- | --- | --- |
| value | <code>Object</code> | map of element name to its value |

<a name="module_TextEditorView#focus"></a>
### textEditorView.focus(atEnd)
Places focus on the editor

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type | Description |
| --- | --- | --- |
| atEnd | <code>boolean</code> | Usefull for multi input editors. Specifies which input should be focused: first                         or last |

<a name="module_TextEditorView#onFocusin"></a>
### textEditorView.onFocusin(e)
Handles focusin event

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type |
| --- | --- |
| e | <code>jQuery.Event</code> | 

<a name="module_TextEditorView#onFocusout"></a>
### textEditorView.onFocusout(e)
Handles focusout event

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type |
| --- | --- |
| e | <code>jQuery.Event</code> | 

<a name="module_TextEditorView#onMousedown"></a>
### textEditorView.onMousedown(e)
Handles mousedown event

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type |
| --- | --- |
| e | <code>jQuery.Event</code> | 

<a name="module_TextEditorView#blur"></a>
### textEditorView.blur()
Turn view into blur

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#getValidationRules"></a>
### textEditorView.getValidationRules() ⇒ <code>Object</code>
Prepares validation rules for usage

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#getRawModelValue"></a>
### textEditorView.getRawModelValue() ⇒ <code>\*</code>
Reads proper model's field value

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#formatRawValue"></a>
### textEditorView.formatRawValue(value) ⇒ <code>string</code>
Converts model value to the format that can be passed to a template as field value

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type |
| --- | --- |
| value | <code>\*</code> | 

<a name="module_TextEditorView#parseRawValue"></a>
### textEditorView.parseRawValue(value) ⇒ <code>\*</code>
Parses value that is stored in model

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type |
| --- | --- |
| value | <code>\*</code> | 

<a name="module_TextEditorView#getModelValue"></a>
### textEditorView.getModelValue() ⇒ <code>string</code>
Returns the raw model value

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#getValue"></a>
### textEditorView.getValue() ⇒ <code>string</code>
Returns the current value after user edit

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
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

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#rethrowEvent"></a>
### textEditorView.rethrowEvent()
Generic handler for DOM events. Used on form to allow processing that events outside view.

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#isChanged"></a>
### textEditorView.isChanged() ⇒ <code>boolean</code>
Returns true if the user has changed the value

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#isValid"></a>
### textEditorView.isValid() ⇒ <code>boolean</code>
Returns true if the user entered valid data

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#updateSubmitButtonState"></a>
### textEditorView.updateSubmitButtonState()
Set a submit button disabled state relevant input value

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#onGenericKeydown"></a>
### textEditorView.onGenericKeydown(e)
Refers keydown action to proper action handler

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  

| Param |
| --- |
| e | 

<a name="module_TextEditorView#onGenericEnterKeydown"></a>
### textEditorView.onGenericEnterKeydown(e)
Generic keydown handler, which handles ENTER

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type |
| --- | --- |
| e | <code>$.Event</code> | 

<a name="module_TextEditorView#onGenericTabKeydown"></a>
### textEditorView.onGenericTabKeydown(e)
Generic keydown handler, which handles TAB

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type |
| --- | --- |
| e | <code>$.Event</code> | 

<a name="module_TextEditorView#onGenericEscapeKeydown"></a>
### textEditorView.onGenericEscapeKeydown(e)
Generic keydown handler, which handles ESCAPE

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type |
| --- | --- |
| e | <code>$.Event</code> | 

<a name="module_TextEditorView#onGenericArrowKeydown"></a>
### textEditorView.onGenericArrowKeydown(e)
Generic keydown handler, which handles ARROWS

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  

| Param | Type |
| --- | --- |
| e | <code>$.Event</code> | 

<a name="module_TextEditorView#getServerUpdateData"></a>
### textEditorView.getServerUpdateData() ⇒ <code>Object</code>
Returns data which should be sent to the server

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
<a name="module_TextEditorView#getModelUpdateData"></a>
### textEditorView.getModelUpdateData() ⇒ <code>Object</code>
Returns data for the model update

**Kind**: instance method of <code>[TextEditorView](#module_TextEditorView)</code>  
