# Items manager

## Components
- Backbone collection for storing list of items
- `itemsManagerEditor` - [jQuery-UI widget] for binding html inputs to the item
- `itemsManagerTable` - [jQuery-UI widget] for rendering list of items

## Usage Example
Define `Backbone.Model` for item:
```js
var ItemModel = Backbone.Model.extend({
    defaults: {
        name : null,
        label: null,
        func: null,
        sorting: null
    }
});
```

Define `Backbone.Collection` for item list:
```js
var ItemCollection = Backbone.Collection.extend({
    model: ItemModel
});
```

Define html for `itemsManagerEditor`:
```html
<div id="editor">
    <input name="name"></input>
    <input name="label"></input>
    <input name="func"></input>
    <input name="sorting"></input>
    <button class="add-button"></button>
    <button class="save-button"></button>
    <button class="cancel-button"></button>
</div>
```

Define html for `itemsManagerTable`:
```html
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Label</th>
            <th>Function</th>
            <th>Sorting</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody class="item-container">
    </tbody>
</table>
```

Define template for item in list:
```
<script id="item-tmpl" type="text/template">
    <tr data-cid="<%= cid %>">
        <td><%= name %></td>
        <td><%= label %></td>
        <td><%= func %></td>
        <td><%= sorting %></td>
        <td class="action-cell">
            <a href="javascript: void(0);" data-collection-action="edit">
                <i class="icon-edit hide-text"></i></a>
            <a href="javascript: void(0);" data-collection-action="delete" data-message="Delete?">
                <i class="icon-trash hide-text"></i></a>
        </td>
    </tr>
</script>
```

Instantiate item collection:
```js
var items = new ItemCollection([
    {
        "name": "a",
        "label": "A",
        // ...
    },
    {
        "name": "b",
        "label": "B",
        // ...
    },
    {
        "name": "c",
        "label": "C",
        // ...
    },
]);
```

Apply `itemsManagerEditor` widget on `div#editor`:
```js
$('div#editor').itemsManagerEditor({
    collection: items
});
```

Apply `itemsManagerTable` widget on `tbody.item-container`:
```js
$('tbody.item-container').itemsManagerTable({
    itemTemplate: #('#item-tmpl').html(),
    collection: items
});
```

[jQuery-UI widget]: <http://api.jqueryui.com/jQuery.widget/>
[jQuery-UI sortable]: <http://api.jqueryui.com/sortable/>
