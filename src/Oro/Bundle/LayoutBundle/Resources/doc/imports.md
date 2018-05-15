# Importing Layout Updates

Syntax:
```yaml
layout:
    actions: []
    imports:
        -
            id: 'customer_user_role_form_actions'
```
or just
```yaml
layout:
    actions: []
    imports:
        - 'customer_user_role_form_actions'
```
In this example, 'customer_user_role_form_actions' is the name of the folder in the layouts/{theme_name}/imports and the unique import identifier. This means that all layout updates will be loaded from the layouts/{theme_name}/imports/customer_user_role_form_actions folder on import statement.

As the result, all actions will be executed if the condition (if exists) of the imported layout update is true. In this case, you do not need any special syntax in the layout updates.

To import the same layout update repeatedly, provide unique ids for all layout blocks using the following special syntax:

# Layout Update in Imports Folder:

```yaml
layout:
    actions:
        - '@setBlockTheme':
            themes: 'AcmeLayoutBundle:layouts:default/layout.html.twig'
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

Double underscore means that the namespace can be provided for these blocks. The namespace should be passed to the import statement in the following way:

```yaml
imports:
    -
        id: 'customer_user_role_form_actions'
        root: 'form_fields_container'
        namespace: 'form_fields'
```

A special `root` parameter will replace `__root` in the imported layout updates. As a result, we will get the following tree:

```yaml
tree:
    form_fields_container: #root option replaces “__root”
        form_fields_update: ~ #namespace option replaces all first underscore of “__”
        form_fields_cancel: ~
```
When you provide a block theme for the imported layout update, the end identifier is not known. To state it, use a special syntax for the block name in the  ```__{unique import identifier}{import block id before namespace added}_widget``` template.

```twig
{% block __customer_user_role_form_actions__update_widget %}
{% endblock %}

{% block __customer_user_role_form_actions__root_widget %}
{% endblock %}
```

You also can provide a template for the block template by the end identifier in the layout update which has an import statement like:

```twig
{% block _form_fields_container_widget %}
{% endblock %}

{% block _form_fields_update_widget %}
{% endblock %}
```
## Referencing Imported Blocks Using block_type_widget_id

When you need the imported block to be rendered without a direct reference to its template name, you can use the TWIG variable `block_type_widget_id` which refers to the twig widget id for current block type, like `container_widget`, `menu_widget`, etc.

For example, here is the customized toolbar element defined in the `DataGridBundle` in the product page (`ProductBundle`): 
```twig
{% block _datagrid_toolbar_mass_actions_widget %}
	...
    <div class="catalog__filter-controls__item">
        <div{{ block('block_attributes') }}>{{ block(block_type_widget_id) }}</div>
    </div>
{% endblock %}
```
*Note:* By default, the element contains the `{{ block_widget(block) }}` which renders the block as a template defined in imports. We replaced it with the `block(block_type_widget_id)` to avoid mentioning the template name.

### Additional Info

Here is a short description of how the toolbar element in our example was imported and what the default way it rendered was.

First, the datagrid toolbar in `DataGridBundle` was imported with the following definitions:

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
            themes: 'AcmeLayoutBundle:layouts:default/layout.html.twig'
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
3) In the `imports/datagrid_toolbar/layout.html.twig` block element `__datagrid_toolbar_mass_actions` looked like:
```twig
{% block __datagrid_toolbar__datagrid_toolbar_mass_actions_widget %}
    <div{{ block('block_attributes') }}>{{ block_widget(block) }}</div>
{% endblock %}
```

Next, we redefined the `imports/datagrid_toolbar/layout.html.twig` block in the `ProductBundle`, which resulted in the following code:
```twig
{% block _datagrid_toolbar_mass_actions_widget %}
	...
    <div class="catalog__filter-controls__item">
        <div{{ block('block_attributes') }}>{{ block_widget(block) }}</div>
    </div>
{% endblock %}
```

We then modified the code to be:

```twig
{% block _datagrid_toolbar_mass_actions_widget %}
	...
    <div class="catalog__filter-controls__item">
        <div{{ block('block_attributes') }}>{{ block(block_type_widget_id) }}</div>
    </div>
{% endblock %}
```
