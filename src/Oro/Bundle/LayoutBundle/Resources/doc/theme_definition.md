# Theme definition

## Overview

This doc describes **what is the theme** and how to define and develop theme for Oro Platform.
A theme is a collection of files that declares the visual presentation for a single page or a group of pages.
Basically, think about **theme** like a skin for your application. Files that it consists are **layout** definitions,
**styles**, **scripts** and whatever related to look & feel of the page.

## Configuration

Configuration files should be placed at `Resources/config/oro/` and called `layout.yml`. 
For now there will be only `themes` node, but in future it may contains another nodes as well.
Current `active theme` could be set on application level in `app/configs/config.yml` under `oro_layout.active_theme` node.

### Themes configuration reference

| Option | Description | Required |
|------- |-------------|----------|
| `label` | Label will be displayed in theme management UI. | yes |
| `logo` | Logo that will be displayed in the UI. | no |
| `screenshot` | Screenshot for preview. This will be displayed in theme management UI. | no |
| `directory` | Directory name where to look up for layout updates. By default equals to theme identifier | no |
| `parent` | Parent theme identifier. By default all themes are descendants of the `base` theme | no |
| `groups` | Group name or names for which it's applicable. By default theme is available in the `main` group as applicable to platform  | no |

You can find additional information if you execute `app/console config:dump-reference OroLayoutBundle` shell command.

**Example:**

```yml
# src/Acme/Bundle/DemoBundle/Resources/config/oro/layout.yml

oro_layout:
    themes:
        oro-gold:
            label:          Nice ORO gold theme
            directory:      OroGold
            parent:         oro-default
```

Where `oro-gold` and `oro-default` are unique theme identifiers. `parent` option may contains `~(null)` in case when 
developer do not want to inherit `base` theme.

## Layout directory structure

Each bundle could provide any number of layout updates for specific theme or for the `base` theme.
 
**Example:**

```
src/
    Acme/
        Bundle/
            AcmeDemoBundle/
                Resources/
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
Also there is possibility to introduce new updates in `app/Resources/layouts/` folder. Override of existing files 
could be also done on *application* level, or via bundle inheritance mechanism (for example updates from `base` theme needs to be modified)

**Example:**

```
app/
    Resources
        layouts/
            new-theme/
                update1.yml
                update2.yml
        views/
        ...
        AcmeDemoBundle/
            layouts/
                base/
                    update1.yml # override of existing update in AcmeDemoBundle
                    ...
        ...
```
```

### Route related updates

Layout update file will be executed  or skipped, depends on its location in directory structure. First nesting level(relative from `layouts/`) 
set the **theme** for which this update is suitable(see `directory` option in theme config), the second level set the route name
for which it suitable. If return to the previous examples we may see, that for `oro-gold` theme `update1.yml` and `update2.yml` will be 
executed for each request, but `route_dependent_update.yml` will be executed only for page that has *route name* `oro_user_edit`.
