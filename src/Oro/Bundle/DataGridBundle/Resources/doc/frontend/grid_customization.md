Grid customization through layouts
==============

Grid can become customizable through option `split_to_cells` of `datagrid` block type in the layout configuration file:

``` yaml
    id: account_users
    ...    
    blockType: datagrid
    options:
        grid_name: frontend-account-account-user-grid
        split_to_cells: true
```
**Note:** By default, grid builds without layouts blocks (`split_to_cells: false`) 

According to `split_to_cells` option layout tree of the grid will have hierarchy like this:
 
```
account_users
    account_users_header_row
        account_users_header_cell_firstName
        account_users_header_cell_lastName
        account_users_header_cell_email
        account_users_header_cell_enabled
        account_users_header_cell_confirmed
    account_users_row
        account_users_cell_firstName
            account_users_cell_firstName_value
        account_users_cell_lastName
            account_users_cell_lastName_value
        account_users_cell_email
            account_users_cell_email_value        
        account_users_cell_enabled
            account_users_cell_enabled_value
        account_users_cell_confirmed
            account_users_cell_confirmed_value 
``` 

Where `account_users` is the main block, which corresponds to block `id` of `datagrid` type. 
Block `account_users` contains two other blocks: `account_users_header_row` and `account_users_row`. First responds to the table header, second - table row. In `account_users_header_row` we can see `<block_id>_cell_<column1...N>` blocks which corresponds to  `<th>...</th>` HTML structure. Columns `column1` ... `columnN` were taken from `datagrid.yml` config file:

``` yaml
    columns:
        firstName:
            type:      string
            data_name: accountUser.firstName
        lastName:
            type:      string
            data_name: accountUser.lastName
        email:
            type:      string
            data_name: accountUser.email
        enabled:
            type:      boolean
            data_name: accountUser.enabled
        confirmed:
            type:      boolean
            data_name: accountUser.confirmed
```

Block `account_users_row` consists of `<block_id>_<column1...N>` which corresponds to `<td>...</td>`. Leaf blocks `<block_id>_cell_<column1...N>_value` holds cell value for row value. 

Just after grid was divided into cells we can manipulate its blocks. 

**Note:** Good choice to investigate grid structure is [Layout Developer Toolbar](../../../../LayoutBundle/Resources/doc/debug_information.md).

For example, we want to hide column `email` from `frontend-account-account-user-grid`. Just remove appropriate header and row columns:
``` yaml
    - '@remove':
        id: account_users_header_cell_email

    - '@remove':
        id: account_users_cell_email
``` 

In another case, suppose we want make `bold` content of column `firstName`. In `layout.yml.twig` you should create template like this:
``` twig
{% block _account_users_cell_firstName_value_widget %}
    <b>{{ block_widget(block) }}</b>
{% endblock %}
```
