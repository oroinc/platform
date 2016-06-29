Layout Subtree View
=================

The layout subtree is used for reload content of some layout block via Ajax request.

Initialization
--------------
Layout update:
```yaml
    layout:
        actions:
            - '@addTree':
                ...
                tree:
                    layout_block_id: ~
```

Add LayoutSubtreeView in block template:
```twig
{% block _layout_block_id_widget %}
    <div id="block_id"
        data-page-component-module="oroui/js/app/components/view-component"
        data-page-component-options="{{ {
            view: 'oroui/js/app/views/layout-subtree-view',
            rootId : block.vars.id,
            reloadEvents: ['reload-on-event']
        }|json_encode }}"
        >
        {{ block_widget(block) }}
    </div>
{% endblock %}
```

Or initialize in JavaScript:
```javascript
    var LayoutSubtreeView = require('oroui/js/app/views/layout-subtree-view');
    var layoutSubtree = new LayoutSubtreeView({
        el: '#block_id',
        rootId: 'layout_block_id',
        reloadEvents: ['reload-on-event']
    });

    //then call reload method
    layoutSubtree.reloadLayout();

    //or trigger reload event in other script
    var mediator = require('oroui/js/mediator');
    mediator.trigger('reload-on-event');
```
