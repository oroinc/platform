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
        page_total:
            extend: grand_total
            per_page: true
            hide_on_full_dataset: true
            columns:
              name:
                  label: 'page total'
        grand_total:          
            columns:
              name:
                  label: 'grand totals'
              contactName:
                  sql_expression: 'COUNT(o.name)'
                  formatter: integer
              closeDate:
                  label: 'Oldest'
                  sql_expression: 'MIN(o.closeDate)'
                  formatter: date
              probability:
                  label: 'Summary'
                  sql_expression: 'SUM(o.probability)'
                  formatter: percent
              statusLabel:
                  label: orocrm.sales.opportunity.status.label
```

**Notes:**
  -- _Column name should be equal as name of correspond column_
  -- **label** can be just a text or translation placeholder (***not required***)
  -- **sql_expression** data aggregation SQL expression (***not required***)
  -- **formatter** backend formatter that will process the column value
  -- available values: date, datetime, decimal, integer, percent
  -- if you add "label" and "query" config, but query aggregation returns nothing -> total's cell will be empty 
  -- generally they'll be shown as "`<label>: <query result>`"
  -- total config can be taken from another total row with **extend** parameter.
  -- **per_page** parameter switch data calculation only for current page data
  -- if **hide_on_full_dataset** is true, then this total row will be hidden on full data set. 