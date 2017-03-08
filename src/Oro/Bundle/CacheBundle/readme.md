OroCacheBundle
===============

The `OroCacheBundle` is responsible to work with a different kind of caches.

Table of Contents
-----------------
 - [Abstract cache services](#abstract-cache-services)
 - [Warm up config cache](#warm-up-config-cache)

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

Warm up config cache
--------------------

The purpose is to update only cache that will be needed by the application without updating the cache of those resources,
that have not been changed. This gives a big performance over the approach when the all cache is updated. Cache warming 
occurs in debug mode whenever you updated the resource files. 

The following example shows how this services can be used:

```yaml
# To register your config dumper:
oro.config.dumper:
    class: 'Oro\Example\Dumper\CumulativeConfigMetadataDumper'
    public: false

# To register your config warmer with oro.config_cache_warmer.provider tag:
oro.configuration.provider.test:
    class: 'Oro\Example\Dumper\ConfigurationProvider'
    tags:
        - { name: oro.config_cache_warmer.provider, dumper: 'oro.config.dumper' }

```

```php
<?php

namespace Oro\Example\Dumper;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;
use Oro\Bundle\CacheBundle\Provider\ConfigCacheWarmerInterface;

class CumulativeConfigMetadataDumper implements ConfigMetadataDumperInterface
{
    
    /**
     * Write meta file with resources related to specific config type
     *
     * @param ContainerBuilder $container container with resources to dump
     */
    public function dump(ContainerBuilder $container)
    {
    }
    
    /**
     * Check are config resources fresh?
     *
     * @return bool true if data in cache is present and up to date, false otherwise
     */
    public function isFresh()
    {
        return true;
    }
}

class ConfigurationProvider implements ConfigCacheWarmerInterface
{
    /**
    * @param ContainerBuilder $containerBuilder
    */
    public function warmUpResourceCache(ContainerBuilder $containerBuilder)
    {
        // some logic
        $resource = new CumulativeResource();
        $containerBuilder->addResource($resource);
    }
}

```

Caching of Symfony Validation rules
-----------------------------------

By default, rules for [Symfony Validation Component](http://symfony.com/doc/current/book/validation.html) are cached using `oro.cache.abstract` service. But you can change this to make validation caching suit some custom requirements. To do this you need to redefine `oro_cache.provider.validation` service.
