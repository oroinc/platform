Localized Values
================

Table of Contents
-----------------
 - [Format values in twig](#format-values-in-twig)
 - [Format values in layout configs](#format-values-in-layout-configs)
 - [Format values in datagrids](#format-values-in-datagrids)

Format values in twig
=====================

Use `localized_value` twig filter

```twig
{# mytwig.html.twig #}
localization.titles|localized_value
```

Format values in layout configs
===============================

Use `locale` Layout data provider and `getLocalizedValue()`.

```yml
# .../Resources/views/layouts/.../myconfig.yml
layout:
    actions:
        - '@add':
            ...
            options:
                ...
                content: '=data["locale"].getLocalizedValue(data["localization"].getTitles())'
```

Format values in datagrids
==========================

Use datagrid property with type `localized_value` and attribute `data_name` to set needed property by path, for example `titles`, `relation.subrelation.titles`.
If current localization not detected, will be joined SQL relation to default fallback values, otherwhise it will be recieved by LocalizationHelper, sorters and filters will be removed.

```yml
# .../Resources/config/oro/datagrids.yml
datagrids:
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
        sorters:
            columns:
                name:
                    data_name: name
                title:
                    data_name: title
        filters:
            columns:
                name:
                    type: string
                    data_name: name
                title:
                    type: string
                    data_name: title
```
