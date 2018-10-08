Selected Fields Providers
=========================

Table of contents
-----------------

- [Overview](#overview)
- [How to Use](#how-to-use)
- [Selected Fields Provider](#selected-fields-provider)
- [Fields Required by Columns](#fields-required-by-columns)
- [Fields Required by Sorters](#fields-required-by-sorters)
- [How to Customize](#how-to-customize)

Overview
--------

The selected fields providers must implement interface `Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProviderInterface`.
Selected fields provider returns an array of field names which must be present in the select statement of the datasource
query according to the given datagrid configuration and parameters. In other words, depending on the datagrid configuration
and parameters (request- and user-specific), it must return the fields needed to be displayed / processed for the datagrid to be rendered correctly. 

Please keep in mind that the word `field` here does not mean an `entity field` or `extended field`, but rather
a field which must be present in the select statement of a query.

OroDatagridBundle provides 2 selected fields providers out-of-box:

* `oro_datagrid.provider.selected_fields.columns` (`Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsFromColumnsProvider`)
* `oro_datagrid.provider.selected_fields.sorters` (`Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsFromSortersProvider`)

These providers are collected in the `oro_datagrid.provider.selected_fields` (`Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProvider`)
service which calls all its inner providers and returns an array of unique field names.

How to Use
----------

Selected fields providers enable fetching field names which must be present in the datasource
query of the datagrid. This data can be used in datagrid extensions which modify the datagrid configuration, add new columns,
sorters, etc. The purpose is **to detect whether it is necessary to add a certain field or join the datasource query as not every
field added to the query is actually displayed to the end user. This can greatly help to improve performance of datagrids that
work with tables which can have many records.**

For example, the `oro_datagrid.provider.selected_fields` service is used in the `oro_entity_extend.datagrid.extension.dynamic_fields` datagrid extension to prevent adding joins for extended fields which are not going to be displayed to the end user.

Selected Fields Provider
------------------------

The `Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProvider` composite provider is declared as
the `oro_datagrid.provider.selected_fields` service which returns selected fields from all inner providers.

Example:

``` php
$selectedFieldsStateProvider = $this->container->get('oro_datagrid.provider.selected_fields');
$selectedFields = $selectedFieldsStateProvider->getSelectedFields($datagridConfiguration, $datagridParameters);
var_export($selectedFields);
// Will output
//['sampleField1', 'sampleColumn2']
```

Fields Required by Columns
--------------------------

The `Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsFromColumnsProvider`  provider is declared as
the `oro_datagrid.provider.selected_fields.columns` service. It returns fields (used in renderable columns) which must be 
present in the select statement of the datasource query.

It uses `Oro\Bundle\DataGridBundle\Provider\State\ColumnsStateProvider` to fetch the current state of columns, then collects 
`data_name` configuration options from columns which are currently `renderable` (visible).

Keep in mind that the resulting array of fields can differ depending on the moment when provider is called, because it
fetches data from the datagrid configuration and state which can be changed by extensions and listeners.

The `oro_datagrid.provider.selected_fields.columns` service is not intended to be used directly. Use the `oro_datagrid.provider.selected_fields` service instead to get a full list of selected fields. However, you can use it if you specifically need those fields that are used in renderable columns.

Fields Required by Sorters
--------------------------

The `Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsFromSortersProvider` provider is declared as
a `oro_datagrid.provider.selected_fields.sorters` service. It returns an array of field names (required by applied sorters) 
which must be present in the select statement of the datasource query.

It uses `Oro\Bundle\DataGridBundle\Provider\State\SortersStateProvider` to fetch the current state of sorters, then collects 
`data_name` configuration options from sorters which are currently applied.

Keep in mind that the resulting array of fields can differ depending on the moment when provider is called, because it
fetches data from the datagrid configuration and state which can be changed by extensions and listeners.

The `oro_datagrid.provider.selected_fields.sorters` service is not intended to be used directly. Use `oro_datagrid.provider.selected_fields` 
service instead to get a full list of selected fields. However, you can use it if you specifically need those fields that are required needed by applied sorters.

How to Customize
----------------

You can create your own selected fields provider by implementing `Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProviderInterface`. 
You should define it as a service with the `oro_datagrid.selected_fields_provider` tag.
