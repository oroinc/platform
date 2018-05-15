# OroNavigationBundle

OroNavigationBundle introduces menu navigation structures in the Oro application UI, enables developers to define menus using YAML configuration files or menu builder services, and provides a possibility for application users to adjust existing menus in the system menu management UI.

**Basic Docs**

* [Your first menu](#first-menu)
* [Rendering Menus](#rendering-menus)

<a name="first-menu"></a>

## Menu data sources

Menu data can come from different sources:

* configs (`SomeBundle/Resources/config/oro/navigation.yml`)
* builders tagged as `oro_menu.builder`
* event listeners for `oro_menu.configure.<menu_alias>` event
* changes made by user in menu management UI

## Your first menu

### Defining menu with PHP Builder

To create menu with Builder it have to be registered as `oro_menu.builder` tag in `services.yml`
alias attribute should be added as well and will be used as menu identifier.

```yaml
# services.yml
parameters:
  acme.main_menu.builder.class: Acme\Bundle\DemoBundle\Menu\MainMenuBuilder

services:
  acme.menu.main:
    class: %acme.main_menu.builder.class%
    tags:
       - { name: oro_menu.builder, alias: main }
```

All menu Builders must implement `Oro\Menu\BuilderInterface` with `build()` method. In `build()` method Bundles manipulate
menu items. All builders are collected in `BuilderChainProvider` which is registered in system as Knp\Menu Provider.
Configurations are collected in Extension and passed into Configuration class. In future more
addition Configurations may be created, for example for getting menu configurations from annotations or some persistent
storage like database. After menu structure created `oro_menu.configure.<menu_alias>` event dispatched, with MenuItem
and MenuFactory available.

``` php
<?php
// Acme/Bundle/DemoBundle/Menu/MainMenuBuilder.php

namespace Acme\Bundle\DemoBundle\Menu;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;

class MainMenuBuilder implements BuilderInterface
{
    public function build(ItemInterface $menu, array $options = array(), $alias = null)
    {
        $menu->setExtra('type', 'navbar');
        $menu->addChild('Homepage', array('route' => 'oro_menu_index', 'extras' => array('position' => 10)));
        $menu->addChild('Users', array('route' => 'oro_menu_test', 'extras' => array('position' => 2)));
    }
}
```

### Menu declaration in YAML

YAML file with default menu declaration is located in `Oro/NavigationBundle/Resources/config/oro/navigation.yml`.
In addition to it, each bundle may have their own menu which must be located in `SomeBundleName/Resource/config/oro/navigation.yml`.
Both types of declaration files have the same format:

```yaml
menu_config:
    templates:
        <menu_type>:                          # menu type code
            template: <template>              # path to custom template for renderer
            clear_matcher: <option_value>
            depth: <option_value>
            current_as_link: <option_value>
            current_class: <option_value>
            ancestor_class: <option_value>
            first_class: <option_value>
            last_class: <option_value>
            compressed: <option_value>
            block: <option_value>
            root_class: <option_value>
            is_dropdown: <option_value>

    items: #menu items
        <key>: # menu item identifier. used as default value for name, route and label, if it not set in options
            acl_resource_id: <string>           # ACL resource Id
            translate_domain: <domain_name>     # translation domain
            translate_parameters:               # translation parameters
            label: <label>                      # label text or translation string template
            name:  <name>                       # name of menu item, used as default for route
            uri: <uri_string>                   # uri string, if no route parameter set
            read_only: <boolean>                # disable ability to edit menu item in UI
            route: <route_name>                 # route name for uri generation, if not set and uri not set - loads from key
            route_parameters:                   # router parameters
            attributes: <attr_list>             # <li> item attributes
            link_attributes: <attr_list>        # <a> anchor attributes
            label_attributes: <attr_list>       # <span> attributes for text items without link
            children_attributes: <attr_list>    # <ul> item attributes for nested lists
            show_non_authorized: <boolean>      # show for non-authorized users
            display: <boolean>                  # disable showing of menu item
            display_children: <boolean>         # disable showing of menu item children
            position: <integer>                 # menu item position
            extras:                             # extra parameters for container renderer
                brand: <string>
                brandLink: <string>

    tree:
        <menu_alias>                            # menu alias
            type: <menu_type>                   # menu type code. Link to menu template section.
            scope_type: <string>                # menu scope type identifier
            read_only: <boolean>                # disable ability to edit menu in UI
            max_nesting_level: <integer>        # menu max nesting level
            children:                           # submenu items
                <links to items hierarchy>
                <key>:
                    merge_strategy: <strategy>  # node merge strategy. possible strategies are replace|move
                    children:
                        <links to items hierarchy>
```

To change merge strategy of tree node there are 2 possible options:
 - move _(default)_ - node with same name will be removed and replaced in tree. Node children will be merged with found node children
 - replace - node with same name and children will be removed and replaced in tree with current node definition

Configuration builder reads all navigation.yml and merges its to one menu configuration. Therefore, developer can add or
replace any menu item from his bundles. Developers can prioritize loading and rewriting of menu's configuration
options via sorting bundles in AppKernel.php.

**IMPORTANT:**  Don't use duplicated item keys in menu tree, this keys must be unique. We strongly recommend to add unique prefixes (namespaces) for your menu items.
For example: `acme_my_menu_item` instead of `my_menu_item`.

### Page Titles

Navigation bundle helps to manage page titles for all routes and supports titles translation.
Rout titles can be defined in navigation.yml file:

```yaml
titles:
    route_name_1: "%parameter% - Title"
    route_name_2: "Edit %parameter% record"
    route_name_3: "Static title"
```

Title can be defined with annotation together with route annotation:

```
@TitleTemplate("Route title with %parameter%")
```

## Rendering Menus

To use configuration loaded from YAML files during render menu, twig-extension with template method oro_menu_render
was created. This renderer takes options from YAML configs ('templates' section), merge its with options from method
arguments and call KmpMenu renderer with the resulting options.

```html
{% block content %}
    <h1>Example menu</h1>
    {{ oro_menu_render('navbar') }}
    {{ oro_menu_render('navbar', array('template' => 'SomeUserBundle:Menu:customdesign.html.twig')) }}
{% endblock content %}
```

## Disabling menu items as part of a feature

The NavigationBundle offers a FeatureConfigurationExtension which introduces the ``navigation_items`` feature configuration
option, which, if a menu item is defined in the feature definition in ``features.yml``, gives the possibility to disable a
menu item as in the example below.
The option supports 2 separators for the menu path: ``.`` and `` > ``.

```yaml
navigation_items:
    - 'menu.submenu.child'
    - 'menu > submenu > child'
```

## Breadcrumb Provider

The goal of breadcrumb provider is to provide possibility to show a breadcrumbs based on specific menu defined in `navigation.yml`.
You can get the breadcrumbs through any existing [menu alias](#menu-declaration-in-yaml). And menu can be created and used for breadcrumbs structure only.

### Usage of breadcrumb provider

For using breadcrumb provider you should create layout update with predefined block type **breadcrumbs** and option **menu_name**:

```yml
# CustomerBundle/Resources/views/layouts/blank/imports/oro_customer_page/oro_customer_page.yml

layout:
    imports:
        -
            id: oro_customer_menu
            root: page_sidebar
    actions:
        - '@add':
            id: breadcrumbs
            parentId: page_main_header
            blockType: breadcrumbs                         #block type
            options:
                menu_name: "oro_customer_breadcrumbs_menu" #menu alias
```

### Usage of breadcrumbs block type

You can avoid usage of breadcrumb provider. For that you should create layout update with predefined block type **breadcrumbs** and option **breadcrumbs**:

```yml
# WebCatalogBundle/Resources/views/layouts/blank/oro_product_frontend_product_index/product_index.yml

layout:
    actions:
        - '@setBlockTheme':
            themes: 'WebCatalogBundle:layouts:blank/oro_product_frontend_product_index/product_index.html.twig'
        - '@addTree':
            items:
                category_breadcrumbs:
                    blockType: breadcrumbs
                    options:
                        breadcrumbs: '=data["category_breadcrumbs"].getItems()'
```
After rendering of breadcrumbs block type you should see menu labels separated by slashes. All breadcrumb items can be clickable,
except the last one, which represents a current page.
