Array datasource
===============

Overview
--------

This datasource provides ability to set data for datagrid from array.

Example
-------

``` yaml
datagrids:
    DATAGRID_NAME_HERE:
        source:
            type: array
```

Configuration
-------------

To configure datasource you need to create datagrid event listener and subscribe on `oro_datagrid.datagrid.build.before.DATAGRID_NAME_HERE` event.

```
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();

        if (!$datasource instanceof ArrayDatasource) {
            throw new UnexpectedTypeException($datasource, ArrayDatasource::class);
        }

        // Crate datagrid source array
        $source = [
            // row 1
            [
                'first_column' => 'Value in first row and first column',
                'second_column' => 'Value in first row and second column'
            ],
            // row 2
            [
                'first_column' => 'Value in second row and first column',
                'second_column' => 'Value in second row and second column'
            ],
            // ...
        ];

        $datasource->setArraySource($source);
    }
```

In the same time you can configure your grid with predefined columns:
```
datagrids:
    DATAGRID_NAME_HERE:
        source:
            type: array
        columns:
            first_column:
                label: Column 1 Label
        sorters:
            columns:
                first_column:
                    data_name: first_column
```

