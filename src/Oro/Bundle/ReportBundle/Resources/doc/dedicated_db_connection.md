Configure Dedicated Database Connection for Reports
---------------------------------------------------

Building complex reports using the report builder can cause heavy load on the database
and impact other critical functions. To isolate reporting from missing critical functions,
set up a dedicated slave database server which will be in sync with the master server and configure
the application to use the slave server to execute report SQL queries on it.

To achieve this, configure a separate DBAL connection using `config/config.yml` and ask the report engine to use it, as illustrated below.

``` yaml
doctrine:
    dbal:
        connections:
            reports:
                driver:   '%database_driver%'
                host:     # slave database host or '%database_host%' if it is the same as master database
                port:     # slave database port or '%database_port%' if it is the same as master database
                dbname:   '%database_name%'
                user:     '%database_user%'
                password: '%database_password%'
                options:  '%database_driver_options%'
                charset:  '%database_charset%'

oro_report:
    dbal:
        connection: reports
        # the "datagrid_prefixes" option is optional and can be used to specify the list of name prefixes for datagrids
        # that are reports and should use the DBAL connection configured in the "connection" option
        datagrid_prefixes:
            - 'oro_reportcrm-'
```
