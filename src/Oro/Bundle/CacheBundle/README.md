# OroCacheBundle

*Note:* This article is published in the Oro documentation library.

OroCacheBundle introduces the configuration of the application data cache storage used by application bundles for different cache types.

## Table of Contents

 - [Abstract Cache Services](#abstract-cache-services)
 - [Caching Static Configuration](#caching-static-configuration)
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

## Caching Static Configuration

A static configuration is a configuration that is defined in configuration files and not depended on the application data.
Usually such configuration are loaded from configuration files located in different bundles, e.g. from
`Resources/config/oro/my_config.yml` files that can be located in any bundle.
There are several possible ways where the collected configuration can be stored to avoid loading and merging it
on each request:

1. As a parameter in the dependency injection container.
   The disadvantage of this approach is not very good DX (Developer Experience) because each time when
   the configuration is changed the whole container should be rebuilt.
2. As a data file in the system cache.
   With this approach DX is better because only this file should be rebuilt after the configuration is changed.
   But the disadvantage of this approach is that the data should be deserialized each time it is requested.
3. As a PHP file in the system cache.
   This approach has the same DX as the previous one. But in additional it has two important advantages:
   the deserialization of the data is not needed and the loaded data is cached by
   [OPcache](http://php.net/manual/en/intro.opcache.php).

To implement 3rd approach for your configuration, you need to do the following steps:

1. Create PHP class that will define the schema of your configuration and validation and merging rules for it. E.g.:

```php
<?php

namespace Acme\Bundle\AcmeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class MyConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('my_config');

        // build the configuration tree here

        return $treeBuilder;
    }
}
```

2. Create the configuration provider PHP class that you will use to get the configuration data. E.g.:

```php
<?php

namespace Acme\Bundle\AcmeBundle\Provider;

use Acme\Bundle\AcmeBundle\DependencyInjection\MyConfiguration;
use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\ResourcesContainerInterface;

class MyConfigurationProvider extends PhpArrayConfigProvider
{
    private const CONFIG_FILE = 'Resources/config/oro/my_config.yml';

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
            'my_config',
            new YamlCumulativeFileLoader(self::CONFIG_FILE)
        );
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            $configs[] = $resource->data;
        }

        return CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            new MyConfiguration(),
            $configs
        );
    }
}
```

3. Register the created configuration provider as a service. E.g.:

```yaml
services:
    acme.my_configuration_provider:
        class: Acme\Bundle\AcmeBundle\Provider\MyConfigurationProvider
        public: false
        arguments:
            - '%kernel.cache_dir%/oro/my_config.php'
            - '@oro_cache.config_cache_factory'
```

4. Register the cache warmer service. E.g.:

```yaml
services:
    acme.my_configuration_warmer:
        class: Oro\Component\Config\Cache\ConfigCacheWarmer
        public: false
        arguments:
            - '@acme.my_configuration_provider'
        tags:
            # add the warmer for this System cache at the begin of the warmers chain
            # to prevent double warmup in case some Application cache depends on this cache
            - { name: kernel.cache_warmer, priority: 200 }
```

## Caching of Symfony Validation rules

By default, rules for [Symfony Validation Component](http://symfony.com/doc/current/book/validation.html) are cached using `oro.cache.abstract` service, but you can change this to make validation caching suit some custom requirements. To do this, you need to redefine `oro_cache.provider.validation` service.
