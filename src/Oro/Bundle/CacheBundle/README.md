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

A static configuration is defined in the configuration files and does not depend on the application data.
Usually such configuration is loaded from configuration files located in different bundles, e.g. from
`Resources/config/oro/my_config.yml` files that can be located in any bundle.
There are several possible ways to store the collected configuration to avoid loading and merging it
on each request:

1. As a parameter in the dependency injection container.
   The disadvantage of this approach is not very good DX (Developer Experience) because each time when
   the configuration is changed the whole container should be rebuilt.
2. As a data file in the system cache.
   This approach has better DX as this is the only file that needs rebuilding after the configuration is changed.
   However, the disadvantage is that data should be deserialized every time it is requested.
3. As a PHP file in the system cache.
   It has the same DX as the previous approach but with two important additional advantages:
   the deserialization of the data is not needed and the loaded data is cached by
   [OPcache](http://php.net/manual/en/intro.opcache.php).

To implement 3rd approach for your configuration, you need to take the following steps:

1. Create PHP class that defines the schema of your configuration and validation and merging rules for it. E.g.:

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
    private const CONFIG_TYPE = 'my_config';
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
    protected function getConfigType(): string
    {
        return self::CONFIG_TYPE;
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
3. Register the created configuration provider as a service using `oro.static_config_provider.abstract` service
   as the parent one. E.g.:

```yaml
services:
    acme.my_configuration_provider:
        class: Acme\Bundle\AcmeBundle\Provider\MyConfigurationProvider
        public: false
        parent: oro.static_config_provider.abstract
        arguments:
            - '%kernel.cache_dir%/oro/my_config.php'
            - '%kernel.debug%'
```

The cache warmer is registered automatically with the priority `200`. This priority adds the warmer at the begin
of the warmers chain that prevents double warmup in case some Application cache depends on the static config cache.
The warmer service ID is the configuration provider service ID prefixed with `.warmer`. If you want to change
the priority or use your own warmer, you can register the service following these naming conventions.
In this case a default warmer will not be registered for your configuration provider.

An example of a custom warmer:

```yaml
services:
    acme.my_configuration_provider.warmer:
        class: Oro\Component\Config\Cache\ConfigCacheWarmer
        public: false
        arguments:
            - '@acme.my_configuration_provider'
        tags:
            - { name: kernel.cache_warmer }
```

If your Application cache depends on your configuration, use the `isCacheFresh($timestamp)` and `getCacheTimestamp()`
methods of the configuration provider to check if the Application cache needs to be rebuilt.
Here is an example how to use these methods:

```php
    private function ensureDataLoaded()
    {
        if (null !== $this->data) {
            return;
        }

        $cachedData = $this->cache->fetch(self::CACHE_KEY);
        if (false !== $cachedData) {
            list($timestamp, $data) = $cachedData;
            if ($this->configurationProvider->isCacheFresh($timestamp)) {
                $this->data = $data;
            }
        }
        if (null === $this->data) {
            $this->data = $this->loadData();
            $this->cache->save(
                self::CACHE_KEY,
                [$this->configurationProvider->getCacheTimestamp(), $this->data]
            );
        }
    }
```

## Caching of Symfony Validation rules

By default, rules for [Symfony Validation Component](http://symfony.com/doc/current/book/validation.html) are cached using `oro.cache.abstract` service, but you can change this to make validation caching suit some custom requirements. To do this, you need to redefine `oro_cache.provider.validation` service.
