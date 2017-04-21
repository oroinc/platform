OroCacheBundle
===============

The `OroCacheBundle` is responsible for operations with various kinds of caches.

Abstract cache services
-----------------------

There are two abstract services you can use as a parent for your cache services:

 - `oro.file_cache.abstract` - this cache should be used for caching data private for each node in a web farm
 - `oro.cache.abstract` - this cache should be used for caching data that need to be shared between nodes in a web farm

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

Read more about the [caching policy and default implementation](Resources/doc/caching_policy.md).

APC cache
---------

There is a possibility to use APC cache and few steps should be completed for this.

First of all, APC should be installed and enabled in the system. After this, the production configuration file (`config_prod.yml`) should be updated with the following parameters:


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

On the last step of the configuration, production cache should be cleared.

Caching of Symfony Validation rules
-----------------------------------

By default, rules for [Symfony Validation Component](http://symfony.com/doc/current/book/validation.html) are cached using `oro.cache.abstract` service, but you can change this to make validation caching suit some custom requirements. To do this, you need to redefine `oro_cache.provider.validation` service.
