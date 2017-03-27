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
            - [ setNamespace, [ 'acme_test' ] ]
```

Also each of these abstract services can be re-declared in the application configuration file, for example:
``` yaml
services:
    oro.cache.abstract:
        abstract: true
        class:                Oro\Bundle\CacheBundle\Provider\PhpFileCache
        arguments:            [%kernel.cache_dir%/oro_data]
```

APC cache
-----------------------

There is a possibility to use APC cache and few steps should be completed for this.

First of all APC should be installed and enabled in the system. After this production configuration file `config_prod.yml` should be changed this way:


``` yaml
doctrine:
    orm:
        auto_mapping: true
        query_cache_driver:    apc
        metadata_cache_driver: apc
        result_cache_driver: apc

services:
    oro.cache.abstract:
        abstract:             true
        class:                Doctrine\Common\Cache\ApcCache
```

As a last step of configuration, production cache should be cleared.

Caching of Symfony Validation rules
-----------------------------------

By default, rules for [Symfony Validation Component](http://symfony.com/doc/current/book/validation.html) are cached using `oro.cache.abstract` service. But you can change this to make validation caching suit some custom requirements. To do this you need to redefine `oro_cache.provider.validation` service.
