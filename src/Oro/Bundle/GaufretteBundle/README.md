OroGaufretteBundle
==================

The goal of this bundle is to simplify working with [KnpGaufretteBundle](https://github.com/KnpLabs/KnpGaufretteBundle).

FileManager
-----------

This class provides a number of methods that helps to do most common file operations.

To declare the manager for your file system use `oro_gaufrette.file_manager` abstract service. E.g.:

```yaml
    oro_acme.file_manager:
        public: false
        parent: oro_gaufrette.file_manager
        arguments:
            - 'acme' # The file system name
```


ReadonlyResourceStream
----------------------

This class implements a stream that can be used for read-only access to a resource stored in Gaufrette file system.
