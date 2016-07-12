Field ACL extension
===================

Field ACL extension allows to check access to fields that uses in the grid's ORM query.

To enable field ACL protection of your field, you should add configuration in `field_acl` section:

```
        fields_acl:                     #section name
            columns:
                 name:                  #column name
                    data_name: a.name   #path that determinate field in query that should be checked
```

Please note that now supports only fields from the root entity of your datagrid's ORM query.

Enabling Field ACL for datagrid wil automatically turn off inline editing for this datagrid.
