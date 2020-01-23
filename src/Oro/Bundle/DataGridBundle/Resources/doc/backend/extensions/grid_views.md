Grid Views Extension
====================

Configuration
-------------

### All grid view label

All grid view label is set in `Oro\Bundle\DataGridBundle\EventListener\DefaultGridViewLoadListener` and in `orodatagrid/js/datagrid/grid-views/view.`

There are 2 ways to set label for All grid view:

* Via option in datagrid config

``` yml
    # ...
    options:
        gridViews:
            allLabel: acme.bundle.translation_key # Translation key for All label
```

* Via pre-defined translation key for the entity which is used in datagrid datasource. Translation key uses the
following pattern: `[vendor].[bundle].[entity].entity_grid_all_view_label`, e.g. for `Oro\Bundle\TranslationBundle\Entity\Language` - `oro.translation.language.entity_grid_all_view_label`.
If bundle name equals entity name, then entity name is skipped, e.g. for `Oro\Bundle\TranslationBundle\Entity\Translation` - `oro.translation.entity_grid_all_view_label`.

If `allLabel` option is not specified and translation key is not translated, then the label for All grid view is created by concatenating `oro.datagrid.gridView.all` translation key and
entity name in plural form, e.g. for Contact entity in english language - "All Contacts".

