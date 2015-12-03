Pager extension:
=======

Overview
--------
This extension provides pagination, also it responsible for passing "pager" settings to view layer.
Now implemented only paging for ORM datasource. It's enabled always for ORM datasource.

One Page Pagination
-------------------

This feature allows to render all grid content in one page (no matter how many rows grid has).
To activate this feature developer has to use option "onePage":

```
    account-account-user-grid:
        options:
            toolbarOptions:
                pagination:
                    onePage: true
        ...
```
