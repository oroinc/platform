OroNavigationBundle
===================

The `OroNavigationBundle` add ability to define menus in different bundles with builders or YAML files
to the [KnpMenuBundle](https://github.com/KnpLabs/KnpMenuBundle). It is also has integrated support of
ACL implementation from Oro UserBundle.

**Basic Docs**

* [Your first menu](#first-menu)
* [Rendering Menus](#rendering-menus)
* [Content Outdating Notifications](./Resources/doc/content_outdating.md)
* [Mediator Handlers](./Resources/doc/mediator-handlers.md)

<a name="first-menu"></a>

### Step 3) Initialize Page Titles
```
php app/console oro:navigation:init
```

## Your first menu

### Defining menu with PHP Builder

To create menu with Builder it have to be registered as oro_menu.builder tag in services.yml
alias attribute should be added as well and will be used as menu identifier.

```yaml
services.yml
parameters:
  acme.main_menu.builder.class: Acme\Bundle\DemoBundle\Menu\MainMenuBuilder

services:
  acme.menu.main:
    class: %acme.main_menu.builder.class%
    tags:
       - { name: oro_menu.builder, alias: main }
```
All menu Builders must implement Oro\Menu\BuilderInterface with build() method. In build() method Bundles manipulate
menu items. All builders are collected in ChainBuilderProvider which is registered in system as Knp\Menu Provider.
Configurations are collected in Extension and passed into Configuration class. In future more
addition Configurations may be created, for example for getting menu configurations from annotations or some persistent
storage like database. After menu structure created oro_menu.configure.<menu_alias> event dispatched, with MenuItem
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

### Page Titles

Navigation bundle helps to manage page titles for all routes and supports titles translation.
Rout titles can be defined in navigation.yml file:
```yaml
titles:
    route_name_1: "%%parameter%% - Title"
    route_name_2: "Edit %%parameter%% record"
    route_name_3: "Static title"
```

Title can be defined with annotation together with route annotation:
```
@TitleTemplate("Route title with %%parameter%%")
```

After titles update following command should be executed:
```
php app/console oro:navigation:init
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

