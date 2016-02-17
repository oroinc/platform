<a name="module_TagsEditorView"></a>
## TagsEditorView ⇐ <code>[AbstractRelationEditorView](../../../../FormBundle/Resources/doc/editor/abstract-relation-editor-view.md)</code>
Tags-select content editor. Please note that it requires column data format
corresponding to [tags-view](../viewer/tags-view.md).

### Column configuration samples:
``` yml
datagrid:
  {grid-uid}:
    inline_editing:
      enable: true
    # <grid configuration> goes here
    columns:
      # Sample 1. Sample configuration
      {column-name-1}:
        frontend_type: tags
        inline_editing:
          editor:
            # view: orotag/js/app/views/editor/tags-editor-view
            view_options:
                permissions:
                    oro_tag_create: true
                    oro_tag_unassign_global: true
          save_api_accessor:
              # usual save api configuration
              route: 'oro_api_post_taggable'
              http_method: 'POST'
              default_route_parameters:
                  entity: <entity-url-safe-class-name>
              route_parameters_rename_map:
                  id: entityId
          autocomplete_api_accessor:
              # usual configuration for tags view
              class: 'oroui/js/tools/search-api-accessor'
              search_handler_name: 'tags'
              label_field_name: 'name'
          validation_rules:
            NotBlank: true
```

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:-----------
inline_editing.editor.validation_rules | Optional. Validation rules. See [documentation](https://goo.gl/j9dj4Y)
inline_editing.editor.permissions      | Permissions
inline_editing.editor.permissions.oro_tag_create | Allows user to create new tag
inline_editing.editor.permissions.oro_tag_unassign_global | Allows user to edit tags assigned by all users

### Constructor parameters

**Extends:** <code>[AbstractRelationEditorView](../../../../FormBundle/Resources/doc/editor/abstract-relation-editor-view.md)</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.model | <code>Object</code> | Current row model |
| options.fieldName | <code>string</code> | Field name to edit in model |
| options.permissions | <code>string</code> | Permissions object |
| options.validationRules | <code>Object</code> | Validation rules. See [documentation here](https://goo.gl/j9dj4Y) |

