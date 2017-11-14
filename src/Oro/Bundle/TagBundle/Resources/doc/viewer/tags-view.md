## TagsView ⇐ `BaseView`

<a name="module_TagsView"></a>
Tags view, able to handle tags array in model.

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
    fieldName: 'tags', // should match tags field name in model
    autoRender: true
});
```

**Extends:** `BaseView`  
