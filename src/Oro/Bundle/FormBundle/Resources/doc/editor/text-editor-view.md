<a name="module_TextEditorView"></a>
## TextEditorView ⇐ `BaseView`
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

**Extends:** `BaseView`  

| Param | Type | Description |
| --- | --- | --- |
| options | `Object` | Options container |
| options.model | `Object` | Current row model |
| options.className | `string` | CSS class name for editor element |
| options.fieldName | `string` | Field name to edit in model |
| options.placeholder | `string` | Placeholder translation key for an empty element |
| options.placeholder_raw | `string` | Raw placeholder value. It overrides placeholder translation key |
| options.validationRules | `Object` | Validation rules. See [documentation here](../reference/js_validation.md#conformity-server-side-validations-to-client-once) |
| options.value | `string` | initial value of edited field |


* [TextEditorView](#module_TextEditorView) ⇐ `BaseView`
  * [.ARROW_LEFT_KEY_CODE](#module_TextEditorView#ARROW_LEFT_KEY_CODE)
  * [._isFocused](#module_TextEditorView#_isFocused)
  * [.getPlaceholder()](#module_TextEditorView#getPlaceholder) ⇒ `string`
  * [.showBackendErrors(backendErrors)](#module_TextEditorView#showBackendErrors)
  * [.getFormState()](#module_TextEditorView#getFormState) ⇒ `Object`
  * [.setFormState(value)](#module_TextEditorView#setFormState)
  * [.focus(atEnd)](#module_TextEditorView#focus)
  * [.onFocusin(e)](#module_TextEditorView#onFocusin)
  * [.onFocusout(e)](#module_TextEditorView#onFocusout)
  * [.onMousedown(e)](#module_TextEditorView#onMousedown)
  * [.blur()](#module_TextEditorView#blur)
  * [.getValidationRules()](#module_TextEditorView#getValidationRules) ⇒ `Object`
  * [.getRawModelValue()](#module_TextEditorView#getRawModelValue) ⇒ `*`
  * [.formatRawValue(value)](#module_TextEditorView#formatRawValue) ⇒ `string`
  * [.parseRawValue(value)](#module_TextEditorView#parseRawValue) ⇒ `*`
  * [.getModelValue()](#module_TextEditorView#getModelValue) ⇒ `string`
  * [.getValue()](#module_TextEditorView#getValue) ⇒ `string`
  * [.rethrowAction()](#module_TextEditorView#rethrowAction) ⇒ `string`
  * [.rethrowEvent()](#module_TextEditorView#rethrowEvent)
  * [.isChanged()](#module_TextEditorView#isChanged) ⇒ `boolean`
  * [.isValid()](#module_TextEditorView#isValid) ⇒ `boolean`
  * [.updateSubmitButtonState()](#module_TextEditorView#updateSubmitButtonState)
  * [.onGenericKeydown(e)](#module_TextEditorView#onGenericKeydown)
  * [.onGenericEnterKeydown(e)](#module_TextEditorView#onGenericEnterKeydown)
  * [.onGenericTabKeydown(e)](#module_TextEditorView#onGenericTabKeydown)
  * [.onGenericEscapeKeydown(e)](#module_TextEditorView#onGenericEscapeKeydown)
  * [.onGenericArrowKeydown(e)](#module_TextEditorView#onGenericArrowKeydown)
  * [.getServerUpdateData()](#module_TextEditorView#getServerUpdateData) ⇒ `Object`
  * [.getModelUpdateData()](#module_TextEditorView#getModelUpdateData) ⇒ `Object`

<a name="module_TextEditorView#ARROW_LEFT_KEY_CODE"></a>
### textEditorView.ARROW_LEFT_KEY_CODE
Arrow codes

**Kind**: instance property of [TextEditorView](#module_TextEditorView)  
<a name="module_TextEditorView#_isFocused"></a>
### textEditorView._isFocused
Internal focus tracking variable

**Kind**: instance property of [TextEditorView](#module_TextEditorView)  
**Access:** protected  
<a name="module_TextEditorView#getPlaceholder"></a>
### textEditorView.getPlaceholder() ⇒ `string`
Returns placeholder

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  
<a name="module_TextEditorView#showBackendErrors"></a>
### textEditorView.showBackendErrors(backendErrors)
Shows backend validation errors

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  

| Param | Type | Description |
| --- | --- | --- |
| backendErrors | `Object` | map of field name to its error |

<a name="module_TextEditorView#getFormState"></a>
### textEditorView.getFormState() ⇒ `Object`
Reads state of form (map of element name to its value)

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  
<a name="module_TextEditorView#setFormState"></a>
### textEditorView.setFormState(value)
Set values to form elements

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  

| Param | Type | Description |
| --- | --- | --- |
| value | `Object` | map of element name to its value |

<a name="module_TextEditorView#focus"></a>
### textEditorView.focus(atEnd)
Places focus on the editor

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  

| Param | Type | Description |
| --- | --- | --- |
| atEnd | `boolean` | Usefull for multi input editors. Specifies which input should be focused: first                         or last |

<a name="module_TextEditorView#onFocusin"></a>
### textEditorView.onFocusin(e)
Handles focusin event

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  

| Param | Type |
| --- | --- |
| e | `jQuery.Event` | 

<a name="module_TextEditorView#onFocusout"></a>
### textEditorView.onFocusout(e)
Handles focusout event

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  

| Param | Type |
| --- | --- |
| e | `jQuery.Event` | 

<a name="module_TextEditorView#onMousedown"></a>
### textEditorView.onMousedown(e)
Handles mousedown event

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  

| Param | Type |
| --- | --- |
| e | `jQuery.Event` | 

<a name="module_TextEditorView#blur"></a>
### textEditorView.blur()
Turn view into blur

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  
<a name="module_TextEditorView#getValidationRules"></a>
### textEditorView.getValidationRules() ⇒ `Object`
Prepares validation rules for usage

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  
<a name="module_TextEditorView#getRawModelValue"></a>
### textEditorView.getRawModelValue() ⇒ `*`
Reads proper model's field value

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  
<a name="module_TextEditorView#formatRawValue"></a>
### textEditorView.formatRawValue(value) ⇒ `string`
Converts model value to the format that can be passed to a template as field value

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  

| Param | Type |
| --- | --- |
| value | `*` | 

<a name="module_TextEditorView#parseRawValue"></a>
### textEditorView.parseRawValue(value) ⇒ `*`
Parses value that is stored in model

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  

| Param | Type |
| --- | --- |
| value | `*` | 

<a name="module_TextEditorView#getModelValue"></a>
### textEditorView.getModelValue() ⇒ `string`
Returns the raw model value

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  
<a name="module_TextEditorView#getValue"></a>
### textEditorView.getValue() ⇒ `string`
Returns the current value after user edit

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  
<a name="module_TextEditorView#rethrowAction"></a>
### textEditorView.rethrowAction() ⇒ `string`
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

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  
<a name="module_TextEditorView#rethrowEvent"></a>
### textEditorView.rethrowEvent()
Generic handler for DOM events. Used on form to allow processing that events outside view.

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  
<a name="module_TextEditorView#isChanged"></a>
### textEditorView.isChanged() ⇒ `boolean`
Returns true if the user has changed the value

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  
<a name="module_TextEditorView#isValid"></a>
### textEditorView.isValid() ⇒ `boolean`
Returns true if the user entered valid data

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  
<a name="module_TextEditorView#updateSubmitButtonState"></a>
### textEditorView.updateSubmitButtonState()
Set a submit button disabled state relevant input value

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  
<a name="module_TextEditorView#onGenericKeydown"></a>
### textEditorView.onGenericKeydown(e)
Refers keydown action to proper action handler

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  

| Param |
| --- |
| e | 

<a name="module_TextEditorView#onGenericEnterKeydown"></a>
### textEditorView.onGenericEnterKeydown(e)
Generic keydown handler, which handles ENTER

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  

| Param | Type |
| --- | --- |
| e | `$.Event` | 

<a name="module_TextEditorView#onGenericTabKeydown"></a>
### textEditorView.onGenericTabKeydown(e)
Generic keydown handler, which handles TAB

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  

| Param | Type |
| --- | --- |
| e | `$.Event` | 

<a name="module_TextEditorView#onGenericEscapeKeydown"></a>
### textEditorView.onGenericEscapeKeydown(e)
Generic keydown handler, which handles ESCAPE

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  

| Param | Type |
| --- | --- |
| e | `$.Event` | 

<a name="module_TextEditorView#onGenericArrowKeydown"></a>
### textEditorView.onGenericArrowKeydown(e)
Generic keydown handler, which handles ARROWS

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  

| Param | Type |
| --- | --- |
| e | `$.Event` | 

<a name="module_TextEditorView#getServerUpdateData"></a>
### textEditorView.getServerUpdateData() ⇒ `Object`
Returns data which should be sent to the server

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  
<a name="module_TextEditorView#getModelUpdateData"></a>
### textEditorView.getModelUpdateData() ⇒ `Object`
Returns data for the model update

**Kind**: instance method of [TextEditorView](#module_TextEditorView)  
