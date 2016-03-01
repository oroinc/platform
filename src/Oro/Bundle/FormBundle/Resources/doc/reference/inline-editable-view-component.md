<a name="module_InlineEditableViewComponent"></a>
## InlineEditableViewComponent ⇐ <code>BaseComponent</code>
Allows to connect inline editors on view pages.
Currently used only for tags-editor. See [index of supported editors](../editor)

**Extends:** <code>BaseComponent</code>  
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
               enable: resource_granted('oro_tag_assign_unassign'),
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
                           oro_tag_create: resource_granted('oro_tag_create'),
                           oro_tag_unassign_global: resource_granted('oro_tag_unassign_global')
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
| options | <code>Object</code> | Options container |
| options._sourceElement | <code>Object</code> | The element to which the view should be connected (passed automatically when                                          page component is [connected through DOM attributes](../../../../UIBundle/Resources/doc/reference/page-component.md)) |
| options.frontend_type | <code>string</code> | frontend type, please find [available keys here](../../public/js/tools/frontend-type-map.js) |
| options.value | <code>\*</code> | value to edit |
| options.fieldName | <code>string</code> | field name to use when sending value to server |
| options.metadata | <code>Object</code> | Editor metadata |
| options.metadata.inline_editing | <code>Object</code> | inline-editing configuration |


* [InlineEditableViewComponent](#module_InlineEditableViewComponent) ⇐ <code>BaseComponent</code>
  * [.initialize](#module_InlineEditableViewComponent#initialize)
    * [new initialize(options)](#new_module_InlineEditableViewComponent#initialize_new)
  * [.resizeTo()](#module_InlineEditableViewComponent#resizeTo)

<a name="module_InlineEditableViewComponent#initialize"></a>
### inlineEditableViewComponent.initialize
**Kind**: instance class of <code>[InlineEditableViewComponent](#module_InlineEditableViewComponent)</code>  
<a name="new_module_InlineEditableViewComponent#initialize_new"></a>
#### new initialize(options)

| Param | Type |
| --- | --- |
| options | <code>Object</code> | 

<a name="module_InlineEditableViewComponent#resizeTo"></a>
### inlineEditableViewComponent.resizeTo()
Resizes editor to base view width

**Kind**: instance method of the <code>[InlineEditableViewComponent](#module_InlineEditableViewComponent)</code>  
