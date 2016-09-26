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
As result all actions will be executed if condition (if exists) of imported layout update is true. In this case you don't need any special syntax in layout updates.

To import same layout update repeatedly, provide unique ids for all layout blocks using the following special syntax:

# Layout update in imports folder:
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

Also special `root` parameter will replace `__root` in imported layout updates. As a result, after replace we will get the following tree:

```yaml
tree:
    form_fields_container: #root option replaces “__root”
        form_fields_update: ~ #namespace option replaces all first underscore of “__”
        form_fields_cancel: ~
```

When you provide a block theme for imported layout update, the end identifier is not known. To state it, use special syntax for the block name in the template ```__{unique import identifier}{import block id before namespace added}_widget```

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
When you need the imported block to be rendered without direct reference to its template name, you can use TWIG variable `block_type_widget_id` which refers to the twig widget id for current block type, like `container_widget`, `menu_widget`, etc.

For example, here is the customized toolbar element defined in `DataGridBundle` in the product page (`ProductBundle`): 
```twig
{% block _datagrid_toolbar_mass_actions_widget %}
	...
    <div class="catalog__filter-controls__item">
        <div{{ block('block_attributes') }}>{{ block(block_type_widget_id) }}</div>
    </div>
{% endblock %}
```
*Note:* By default, element contained the `{{ block_widget(block) }}` which renders the block as a template defined in imports. We replaced it with `block(block_type_widget_id)` to avoid mentioning the template name.

### Additional info
You might be wondering how the toolbar element in our example was imported and what was the default way it rendered. Here is the story:

First, in `DataGridBundle`, the datagrid toolbar was imported with the following definitions:
1) Id in the `layout.yml`:
```yaml
layout:
    actions:
		...
    imports:
        -
            id: datagrid_toolbar		
``` 
2) Item tree in `imports/datagrid_toolbar/layout.yml` (block element `__datagrid_toolbar_mass_actions`):
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
3) In `imports/datagrid_toolbar/layout.html.twig` block element `__datagrid_toolbar_mass_actions` looked like:
```twig
{% block __datagrid_toolbar__datagrid_toolbar_mass_actions_widget %}
    <div{{ block('block_attributes') }}>{{ block_widget(block) }}</div>
{% endblock %}
```

Next, in `ProductBundle` we redefined the `imports/datagrid_toolbar/layout.html.twig` block, which resulted in the following code:
```twig
{% block _datagrid_toolbar_mass_actions_widget %}
	...
    <div class="catalog__filter-controls__item">
        <div{{ block('block_attributes') }}>{{ block_widget(block) }}</div>
    </div>
{% endblock %}
```

that we modified to be:

```twig
{% block _datagrid_toolbar_mass_actions_widget %}
	...
    <div class="catalog__filter-controls__item">
        <div{{ block('block_attributes') }}>{{ block(block_type_widget_id) }}</div>
    </div>
{% endblock %}
```
