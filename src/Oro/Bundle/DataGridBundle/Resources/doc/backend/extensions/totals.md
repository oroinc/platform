Pager extension:
=======

Overview
--------
This extension provides totals aggregation, which will be shown in grid's footer (tfoot).

Settings
---------
Totals setting should be placed under `totals` tree node.

```
datagrid:
  demo:
    source:
      totals:
        columns:
          name:
              label: 'TOTALS'
          contactName:
              query: 'COUNT(o.name)'
              formatter: integer
          closeDate:
              label: 'Oldest'
              query: 'MIN(o.closeDate)'
              formatter: date
          probability:
              label: 'Summary'
              query: 'SUM(o.probability)'
              formatter: percent
          statusLabel:
              label: orocrm.sales.opportunity.status.label
```

**Notes:**
  -- _Column name should be equal as name of correspond column_
  -- **label** can be just a text or translation placeholder (***not required***)
  -- **query** data aggregation SQL query (***not required***)
  -- **formatter** backend formatter that will process the column value
  -- -- available values: date, datetime, decimal, integer, percent
  
  -- if you add "label" and "query" config, but query aggregation returns nothing -> total's cell will be empty 
  -- generally they'll be shown as "`<label>: <query result>`"