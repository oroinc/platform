## InlineEditableViewComponent ⇐ `BaseComponent`
<a name="module_InlineEditableViewComponent"></a>
Allows to connect inline editors on view pages.
Currently used only for tags-editor. See [index of supported editors](../editor)

**Extends:** `BaseComponent`  
**Todo**

- [ ] update after connecting other editors

Sample:

```twig
{% import 'OroUIBundle::macros.html.twig' as UI %}
<div {{ UI.renderPageComponentAttributes({
   module: 'oroform/js/app/components/inline-editable-view-component',
   options: {
       frontend_type: 'tags',
       value: oro_tag_get_list(entity),
       fieldName: 'tags',
       metadata: {
           inline_editing: {
               enable: is_granted('oro_tag_assign_unassign'),
               save_api_accessor: {
                   route: 'oro_api_post_taggable',
                   http_method: 'POST',
                   default_route_parameters: {
                       entity: oro_class_name(entity, true),
                       entityId: entity.id
                   }
               },
               autocomplete_api_accessor: {
                   class: 'oroui/js/tools/search-api-accessor',
                   search_handler_name: 'tags',
                   label_field_name: 'name'
               },
               editor: {
                   view_options: {
                       permissions: {
                           oro_tag_create: is_granted('oro_tag_create')
                       }
                   }
               }
           }
       }
   }
}) }}></div>
```


| Param | Type | Description |
| --- | --- | --- |
| options | `Object` | Options container |
| options.\_sourceElement | `Object` | The element to which the view should be connected (passed automatically when page component is [connected through DOM attributes](../../../../UIBundle/Resources/doc/reference/page-component.md)) |
| options.frontend_type | `string` | frontend type, please find [available keys here](../../public/js/tools/frontend-type-map.js) |
| options.value | `*` | value to edit |
| options.fieldName | `string` | field name to use when sending value to server |
| options.metadata | `Object` | Editor metadata |
| options.metadata.inline_editing | `Object` | inline-editing configurat

* [InlineEditableViewComponent](#module_InlineEditableViewComponent) ⇐ `BaseComponent`
  * [.initialize](#module_InlineEditableViewComponent#initialize)
    * [new initialize(options)](#new_module_InlineEditableViewComponent#initialize_new)
  * [.resizeTo()](#module_InlineEditableViewComponent#resizeTo)

<a name="module_InlineEditableViewComponent#initialize"></a>
### inlineEditableViewComponent.initialize
**Kind**: instance class of [InlineEditableViewComponent](#module_InlineEditableViewComponent)  
<a name="new_module_InlineEditableViewComponent#initialize_new"></a>
#### new initialize(options)

| Param | Type |
| --- | --- |
| options | `Object` | 

<a name="module_InlineEditableViewComponent#resizeTo"></a>
### inlineEditableViewComponent.resizeTo()
Resizes editor to base view width

**Kind**: instance method of the [InlineEditableViewComponent](#module_InlineEditableViewComponent)  
