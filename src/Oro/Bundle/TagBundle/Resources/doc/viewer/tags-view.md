<a name="module_TagsView"></a>
## TagsView ⇐ <code>BaseView</code>
Tags view, able to handle plain array of `tags`.

Usage sample:
```javascript
var tagsView = new TagsView({
    model: new Backbone.Model({
        tags: [
            {id: 1, name: 'tag1'},
            {id: 2, name: 'tag2'},
            // ...
        ]
    }),
    fieldName: 'tags' // should match model tags field name,
    autoRender: true
});
```

**Extends:** <code>BaseView</code>  
