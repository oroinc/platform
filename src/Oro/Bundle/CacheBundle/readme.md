OroCacheBundle
===============

The `OroCacheBundle` is responsible to work with a different kind of caches.

Abstract cache services
-----------------------

There are two abstract services you can use as a parent for your cache services:

 - `oro.file_cache.abstract` - this cache should be used to caching data private for each node in a web farm
 - `oro.cache.abstract` - this cache should be used to caching data which need to be shared between nodes in a web farm

The following example shows how this services can be used:
``` yaml
services:
    acme.test.cache:
        public: false
        parent: oro.cache.abstract
        calls:
            - [ setNamespace, [ "acme_test.cache" ] ]
```

Also each of these abstract services can be re-declared in the application configuration file, for example:
``` yaml
services:
    oro.cache.abstract:
        abstract: true
        class:                Oro\Bundle\CacheBundle\Provider\PhpFileCache
        arguments:            [%kernel.cache_dir%/oro_data]
```
