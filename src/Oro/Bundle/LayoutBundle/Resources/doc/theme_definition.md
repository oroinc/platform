# Theme definition

## Overview

This documentation describes **what a theme is**, and how to define and develop layout themes for *OroPlatform*.

A **theme** is a collection of files that declares the visual presentation for a single page or a group of pages.
Basically, think of a **theme** as a skin for your application. Files, that the theme consists of, are [layout updates](./layout_update.md), **styles**, **scripts** and anything else related to the look and feel of the page.

## Configuration

The configuration file should be placed at theme folder and named `theme.yml`, for example `DemoBundle/Resources/views/layouts/first_theme/theme.yml`
Theme folder(name) must match [a-zA-Z][a-zA-Z0-9_\-:]* expression.

### Themes configuration reference

| Option | Description | Required |
|------- |-------------|----------|
| `label` | Label will be displayed in the theme management UI. | yes |
| `logo` | Logo that will be displayed in the UI. | no |
| `screenshot` | Screenshot for preview. This will be displayed in the theme management UI. | no |
| `directory` | Directory name where to look for layout updates. By default, equals to the theme identifier | no |
| `parent` | Parent theme identifier | no |
| `groups` | Group name or names for which it's applicable. By default, theme is available in the `main` group and applicable to the platform  | no |

The `active theme` could be set on the application level in `configs/config.yml` under the `oro_layout.active_theme` node.
You can find additional information if you execute the `bin/console config:dump-reference OroLayoutBundle` shell command.

**Example:**

```yaml
# src/Acme/Bundle/DemoBundle/Resources/views/layouts/base/theme.yml
# The layout theme that is used to add the page content and common page elements
# for all themes in "main" group
label:  ~ # this is a "hidden" theme
groups: [ main ]

# src/Acme/Bundle/DemoBundle/Resources/views/layouts/oro/theme.yml
# Default layout theme for OroPlatform
label:  Oro Theme
icon:   bundles/oroui/themes/oro/images/favicon.ico
parent: base
groups: [ main ]

# src/Acme/Bundle/DemoBundle/Resources/views/layouts/oro-gold/theme.yml
label:          Nice ORO gold theme
directory:      OroGold
parent:         oro
```

Where `base`, `oro` and `oro-gold` are unique theme identifiers.

## Theme layout directory structure

Each bundle can provide any number of layout updates for any theme.
 
**Example:**

```
src/
    Acme/
        Bundle/
            AcmeDemoBundle/
                Resources/
                    views/
                        layouts/
                            base/
                                update1.yml
                                update2.yml
                                ...
                            oro-gold/
                                update1.yml
                                update2.yml
                                oro_user_edit/
                                    route_dependent_update.yml
                                ...
```

Also there is a possibility to introduce new updates in `app/Resources/views/layouts/` folder. Overriding existing files
can be also done on the *application* level (*TODO coming soon*), or via the bundle inheritance mechanism 
(for example updates from the `base` theme need to be modified)

**Example:**

```
app/
    Resources
        views/
            layouts/
                new-theme/
                    update1.yml
                    update2.yml
        ...
        AcmeDemoBundle/
            views/
                layouts/
                    base/
                        update1.yml # override of existing update in AcmeDemoBundle
                        ...
        ...
```

### Route related updates

The execution of a layout update file depends on its location in directory structure. The first nesting level (relative to `layouts/`)
sets the **theme** for which this update is suitable (see `directory` option in theme config), the second level sets the route name
for which it is suitable. Considering our previous examples, we may see that for the `oro-gold` theme `update1.yml` and `update2.yml` will be
executed for every request, but `route_dependent_update.yml` will be executed only for a page that has the *route name* equals to `oro_user_edit`.

Developer reference
-------------------

Here is a list of key classes involved in the theme layout search process:

 - [ThemeExtension](../../../../Component/Layout/Extension/Theme/ThemeExtension.php) - the **layout extension** responsible for obtaining
    updates depending on current context.
 - [ResourceIterator](../../../../Component/Layout/Extension/Theme/Model/ResourceIterator.php) - iterates through known layout updates and accepts those
    that match given criteria.
