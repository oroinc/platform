Field ACL extension
===================

[Field ACL extension](../../../Extension/FieldAcl/FieldAclExtension.php) allows to check access to grid columns. Currently it is implemented only for ORM datasource.

To enable field ACL protection for a column, you should use `field_acl` section in a datagrid declaration:

```
        fields_acl:                     #section name
            columns:
                 name:                  #column name
                    data_name: a.name   #the path to a field which ACL should be used to protect the column
```

Please note that now only fields from the root entity of a datagrid's ORM query are supported.
