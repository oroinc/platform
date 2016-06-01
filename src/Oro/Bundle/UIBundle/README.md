OroUIBundle
===========

User interface layouts and controls.

## Table of Contents

- [Client Side Architecture](./Resources/doc/reference/client-side-architecture.md)
- [Page Component](./Resources/doc/reference/page-component.md)
- [Mediator Handlers](./Resources/doc/reference/mediator-handlers.md)
- [Client Side Navigation](./Resources/doc/reference/client-side-navigation.md)
- [TWIG Filters](./Resources/doc/reference/twig-filters.md)
- [JavaScript Widgets](./Resources/doc/reference/widgets.md)
- [Layout Subtree View](./Resources/doc/reference/client-side/layout-subtree-view.md)
- [Items Manager](./Resources/doc/reference/items-manager.md)
- [Content providers](./Resources/doc/reference/content-providers.md)
- [Loading Mask View](./Resources/doc/reference/client-side/loading-mask-view.md)
- [Scroll Data Customization](./Resources/doc/reference/scroll-data-customization.md)
- [formatters](./Resources/doc/reference/formatters.md)
- [Dynamic Assets](./Resources/doc/dynamic-assets.md)
- [Input Widgets](./Resources/doc/reference/input-widgets.md)

## Configuration Settings

- oro_ui.application_url   - application frontend URL
- oro_ui.application_name  - application name to display in header
- oro_ui.application_title - application title for name reference in header

## Introduction to placeholders

In order to improve layouts and make them more flexible a new twig token `placeholder` is implemented. It allows us to combine several blocks (templates or actions) and output them in different places in twig templates. This way we can customize layouts without modifying twig templates.

### Placeholder declaration in YAML

Placeholders can be defined in any bundle under `/SomeBundleName/Resource/placeholders.yml`

```yaml
items:                             # items to use in placeholders (templates or actions)
 <item_name>:                      # any unique identifier
    template: <template>           # path to custom template for renderer
 <another_item_name>:
    action: <action>               # action name (e.g. OroSearchBundle:Search:searchBar)

placeholders:
  <placeholder_name>:
    items:
      <item_name>:
        order: 100                 # sort order in placeholder
      <another_item_name>:
        order: 200
      <one_more_item_name>: ~      # sort order will be set to 0
```

Any configuration defined in bundle `placeholders.yml` file can be overridden in `app/config/config.yml` file.

```yaml
oro_ui:
    placeholders:
        <placeholder_name>:
            items:
                <item_name>:
                    remove: true   # remove item from placeholder
        <another_placeholder_name>:
            items:
                <item_name>:
                    order: 200     # change item order in placeholder
```

Each placeholder item can have the following properties:

 - **template** or **action** - The path to TWIG template or controller action is used to rendering the item.
 - **applicable** - The condition indicates whether the item can be rendered or not.
 - **acl** - The ACL resource(s). Can be a string or array of strings. Can be used to restrict access to the item. If several ACL resources are provided an access is granted only if all of them grant an access.
 - **data** - An additional data to be passed to TWIG template or controller.

Each property can be a constant or some expression supported by [System Aware Resolver Component](../../Component/Config/Resources/doc/system_aware_resolver.md). Examples can be found in existing *placeholders.yml* files.

### Rendering placeholders

To render placeholder content in twig template we need to put

```html
{% placeholder <placeholder_name> %}
```

Additional options can be passed to all placeholder child items using `with` e.g.

```html
{% placeholder <placeholder_name> with {'form' : form} %}
```

## Templates Hinting

UIBundle allows to enable templates hinting and in such a way helps to frontend developer to find proper template.
This option can be enabled in application configuration with redefining base template class for twig:

```yaml
twig:
    base_template_class: Oro\Bundle\UIBundle\Twig\Template
```

As a result of such change user can find HTML comments on the page
```html
<!-- Start Template: BundleName:template_name.html.twig -->
...
<!-- End Template: BundleName:template_name.html.twig -->
```
or see "template_name" variable for AJAX requests that expecting JSON
```json
"template_name":"BundleName:template_name.html.twig"
```

The templates hinting is enabled by default in development mode.
