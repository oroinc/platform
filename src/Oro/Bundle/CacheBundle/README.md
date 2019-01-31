# OroCacheBundle

*Note:* This article is published in the Oro documentation library.

OroCacheBundle introduces the configuration of the application data cache storage used by application bundles for different cache types.

## Table of Contents

 - [Abstract Cache Services](#abstract-cache-services)
 - [Warming up Config Cache](#warming-up-config-cache)
 - [Caching of Symfony Validation rules](#caching-of-symfony-validation-rules)

## Abstract Cache Services

There are three abstract services you can use as a parent for your cache services:

 - `oro.file_cache.abstract` - this cache should be used for caching data private for each node in a web farm
 - `oro.cache.abstract` - this cache should be used for caching data that need to be shared between nodes in a web farm
 - `oro.cache.abstract.without_memory_cache` - the same as `oro.cache.abstract` but without using additional in-memory caching, it can be used to avoid unnecessary memory usage and performance penalties if in-memory caching is not needed, e.g. you implemented some more efficient in-memory caching strategy around your cache service

The following example shows how this services can be used:

``` yaml
services:
    acme.test.cache:
        public: false
        parent: oro.cache.abstract
        calls:
            - [ setNamespace, [ 'acme_test' ] ]
```

Also `oro.file_cache.abstract` and `oro.cache.abstract` services can be re-declared in the application configuration file, for example:

``` yaml
services:
    oro.cache.abstract:
        abstract: true
        class:                Oro\Bundle\CacheBundle\Provider\PhpFileCache
        arguments:            [%kernel.cache_dir%/oro_data]
```

The `oro.cache.abstract.without_memory_cache` service is always declared automatically based on `oro.cache.abstract` service.

Read more about the [caching policy and default implementation](Resources/doc/caching_policy.md).

## Warming up Config Cache

The purpose is to update only cache that will be needed by the application without updating the cache of those resources,
that have not been changed. This gives a big performance over the approach when the all cache is updated. Cache warming 
occurs in debug mode whenever you updated the resource files. 

The following example shows how to create a confiruration provider and register cache warmer for it:

```php
<?php

namespace Acme\Bundle\AcmeBundle\Provider;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\ResourcesContainerInterface;
use Symfony\Component\Config\ConfigCacheFactoryInterface;

class ConfigurationProvider extends PhpArrayConfigProvider
{
    private const CONFIG_FILE = 'Resources/config/oro/test_config.yml';

    /**
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->doGetConfig();
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $configs = [];
        $configLoader = new CumulativeConfigLoader(
            'acme_test_config',
            new YamlCumulativeFileLoader(self::CONFIG_FILE)
        );
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            $configs[] = $resource->data;
        }

        return CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            new Configuration(),
            $configs
        );
    }
}
```

```yaml
services:
    acme.configuration_provider:
        class: Acme\Bundle\AcmeBundle\Provider\ConfigurationProvider
        arguments:
            - '%kernel.cache_dir%/oro/test_config.php'
            - '@oro_cache.config_cache_factory'

    acme.configuration_warmer:
        class: Oro\Component\Config\Cache\ConfigCacheWarmer
        public: false
        arguments:
            - '@acme.configuration_provider'
        tags:
            - { name: kernel.cache_warmer }
```

## Caching of Symfony Validation rules

By default, rules for [Symfony Validation Component](http://symfony.com/doc/current/book/validation.html) are cached using `oro.cache.abstract` service, but you can change this to make validation caching suit some custom requirements. To do this, you need to redefine `oro_cache.provider.validation` service.
