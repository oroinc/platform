Totals extension:
=======

Overview
--------
This extension provides totals aggregation, which will be shown in grid's footer (tfoot).

Settings
---------
Totals setting should be placed under `totals` tree node.

```yml
datagrids:
  demo:
    source:
       [...]
    totals:
      page_total:
          extends: grand_total
          per_page: true
          hide_if_one_page: true
          columns:
            name:
                label: 'page total'
      grand_total:          
          columns:
            name:
                label: 'grand total'
            contactName:
                expr: 'COUNT(o.name)'
                formatter: integer
            closeDate:
                label: 'Oldest'
                expr: 'MIN(o.closeDate)'
                formatter: date
            probability:
                label: 'Summary'
                expr: 'SUM(o.probability)'
                formatter: percent
            budget:
                label: 'Budget Amount'
                expr: 'SUM(o.budget)'
                formatter: currency
                divisor: 100
            statusLabel:
                label: oro.sales.opportunity.status.label
```

**Notes:**
- _Column name should be equal as name of correspond column_
- **label** can be just a text or translation placeholder (***not required***)
- **expr** data aggregation SQL expression (***not required***)
- **formatter** backend formatter that will process the column value
- available values: date, datetime, decimal, integer, percent
- if you add "label" and "query" config, but query aggregation returns nothing -> total's cell will be empty 
- generally they'll be shown as "`<label>: <query result>`"
- total config can be taken from another total row with **extends** parameter.
- **per_page** parameter switch data calculation only for current page data
- if **hide_if_one_page** is true, then this total row will be hidden on full data set.
- **divisor** if you need to divide the value by a number before rendering it to the user (***not required***)
