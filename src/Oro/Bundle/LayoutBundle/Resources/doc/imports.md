Importing layout updates
==============

Syntax:
```yaml
layout:
    actions: []
    imports:
        -
            id: 'account_user_role_form_actions'
```
or just
```yaml
layout:
    actions: []
    imports:
        - 'account_user_role_form_actions'
```
In this example 'account_user_role_form_actions' is the name of the folder in layouts/{theme_name}/imports and the unique import identifier.
It means, that all layout updates will be loaded from layouts/{theme_name}/imports/account_user_role_form_actions folder on import statement.
As result all actions will be executed if condition (if exists) of imported layout update is true. At this case you don't need any special syntax in layout updates.

If you want import same layout update repeatedly you should provide unique ids for all layout blocks. For this case use special syntax:

Layout update in imports folder:
```yaml
layout:
    actions:
        - '@setBlockTheme':
            themes: 'layout.html.twig'
        - '@addTree':
            items:
                __update:
                    blockType: button
                    options:
                        action: submit
                        text: 'Save label'
                __cancel:
                    blockType: link
                    options:
                        route_name: oro_route_index
                        text: 'Cancel label'
                        attr:
                            'class': btn
            tree:
                #'__root' reserved root import option
                __root:
                    __update: ~
                    __cancel: ~
```

Double underscore means namespace can be provided for this blocks. Namespace should be passed to import statement like:

```yaml
imports:
    -
        id: 'account_user_role_form_actions'
        root: 'form_fields_container'
        namespace: 'form_fields'
```

Also special `root` parameter will replace `__root` in imported layout updates. As result after replace we will get next tree:

```yaml
tree:
    form_fields_container: #root option replaces “__root”
        form_fields_update: ~ #namespace option replaces all first underscore of “__”
        form_fields_cancel: ~
```

If you provide block theme for imported layout update end identifier hasn't known. Use special syntax for block name in template ```__{unique import identifier}{import block id before namespace added}_widget```

```twig
{% block __account_user_role_form_actions__update_widget %}
{% endblock %}

{% block __account_user_role_form_actions__root_widget %}
{% endblock %}
```

You also can provide template for block template by end identifier in layout update which has import statement like:

```twig
{% block _form_fields_container_widget %}
{% endblock %}

{% block _form_fields_update_widget %}
{% endblock %}
```
Referencing imported blocks using block_type_widget_id
----------------------------------------------------
Sometimes you need to render block for imported block type without direct reference to it's template name.
For this purpose you can use TWIG variable `block_type_widget_id`. It's equal to twig widget id for current block type (for example: container_widget, menu_widget, etc.)

Suppose we need to customize defined in `DataGridBundle` toolbar element for our product page. 
In `DataGridBundle` we have already defined this strucure:

1) `layout.yml` with imported `datagrid_toolbar`:
```yaml
layout:
    actions:
		...
    imports:
        -
            id: datagrid_toolbar		
``` 
2) In `imports/datagrid_toolbar/layout.yml` block element `__datagrid_toolbar_mass_actions` will be looks like:
```yaml
layout:
    actions:
        - '@setBlockTheme':
            themes: 'layout.html.twig'
        - '@addTree':
            items:
                __datagrid_toolbar:
                    blockType: container
                __datagrid_toolbar_actions_container:
                    blockType: container
                __datagrid_toolbar_mass_actions:
                    blockType: container
            ...
            tree:
                __root:
                    __datagrid_toolbar:
                        __datagrid_toolbar_sorting: ~
                        __datagrid_toolbar_actions_container:
                            __datagrid_toolbar_mass_actions: ~  
            ...                                  

```
3) In `imports/datagrid_toolbar/layout.html.twig` block element `__datagrid_toolbar_mass_actions` will look like:
```twig
{% block __datagrid_toolbar__datagrid_toolbar_mass_actions_widget %}
    <div{{ block('block_attributes') }}>{{ block_widget(block) }}</div>
{% endblock %}
```
And in `ProductBundle` we redefine this block:
```twig
{% block _datagrid_toolbar_mass_actions_widget %}
	...
    <div class="catalog__filter-controls__item">
        <div{{ block('block_attributes') }}>{{ block_widget(block) }}</div>
    </div>
{% endblock %}
```
Function `block_widget(block)` will render this block as template defined in imports. But that's not what we need: to render block type template properly lets use `block_type_widget_id`:

```twig
{% block _datagrid_toolbar_mass_actions_widget %}
	...
    <div class="catalog__filter-controls__item">
        <div{{ block('block_attributes') }}>{{ block(block_type_widget_id) }}</div>
    </div>
{% endblock %}
```
