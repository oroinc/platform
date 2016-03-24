#oro/datagrid

##Table of content
- [Events](#events)

##Events

###Mediator events
Datagrid listens on mediator for events:

- `datagrid:setParam:<gridName>` - `param`, `value`
  Set additional datagrid parameters

- `datagrid:removeParam:<gridName>` - `param`
  Remove additional datagrid parameter

- `datagrid:restoreState:<gridName>` - `columnName`, `dataField`, `included`, `excluded`
  Restore checkboxes state
  
- `datagrid:restoreChangeset:<gridName>` - `dataField`, `changeset`
  Restore select-cells state

- `datagrid:doRefresh:<gridName>`
  Refresh datagrid

- `datagrid:doReset:<gridName>`
  Reset datagrid state

###DOM events
Datagrid emits DOM events on its $el element:

- `datagrid:change:<gridName>` - `model`
