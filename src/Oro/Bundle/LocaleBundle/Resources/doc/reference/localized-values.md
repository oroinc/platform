Localized Values
================

Table of Contents
-----------------
 - [Format localized values in twig](#format-localized-values-in-twig)
 - [Format localized values in layout configs](#format-localized-values-in-layout-configs)
 - [Format localized values in datagrids](#format-localized-values-in-datagrids)

Format localized values in twig
===============================

Use `localized_value` twig filter

```twig
{# mytwig.html.twig #}
localization.titles|localized_value
```

Format localized values in layout configs
=========================================

Use `localized_value` ExpressionFunction

```yml
# .../Resources/views/layouts/.../myconfig.yml
layout:
    actions:
        - @add:
            ...
            options:
                ...
                content: '=localized_value(data["localization"].getTitles())'
```

Format localized values in datagrids
====================================

Use datagrid property with type `localized_value` and attribute `data_name` to set needed property by path, for example `titles`, `relation.subrelation.titles`
If current localization not detected, will be join direct SQL relation to default fallback value, otherwhise values will be recieved by LocalizationHelper, sorters and filters will be removed.

```yml
# .../Resources/config/datagrid.yml
datagrid:
    my-localizations-grid:
        source:
            type: orm
            query:
                select:
                    - l.id
                    - l.name
                from:
                    - { table: %oro_locale.entity.localization.class%, alias: l }
        properties:
            title:
                type: localized_value # property type
                data_name: titles # property path to localized property of an entity
        columns:
            name:
                label: Name
            title:
                label: Title
```
