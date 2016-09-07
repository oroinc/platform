Export extension
================
This extension provides a functionality to export grid rows. This allows you to export rows from all pages. The exported data will be the same as on a grid, including filters and sorting.

Configuration
-------------
To enable export functionality you just need to add `export` option to a configuration of your grid. For example:

``` yaml
datagrids:
    accounts-grid:
        ...
        options:
            export: true
```

After that `Export` button will be displayed on the top left corner of a grid. To export grid data an user just need to click this button and selects a format of exporting data (currently only CSV format is implemented).

If you need allow to export grid data in other formats you need to configure your grid properly. For example to allow export data in CSV and PDF formats you can use the following configuration:

``` yaml
datagrids:
    my-grid:
        ...
        options:
            export:
                csv: { label: oro.grid.export.csv }
                pdf: { label: acme.grid.export.pdf }
```

Also you need to implement and register a writer for new export format. To register a writer in dependency container you should use the following naming convention: `oro_importexport.writer.echo.[format]`. So, a writer for PDF should be registerd as `oro_importexport.writer.echo.pdf`.
You can use [existing CSV writer](../../../../../ImportExportBundle/Writer/CsvEchoWriter.php) as an example for your writer.
