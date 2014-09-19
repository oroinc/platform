Cumulative Resources
--------------------

This resource type provides a way to load configuration from any bundle without an additional registration of configuration files in each bundle.

Introduction
------------
Please imagine your bundle need to load configuration from `Resources\config\acme.yml` file located in any other bundle. In other words you need to allow other bundles to provide additional configuration to your bundle. In this case a bundle which need this configuration can use [CumulativeConfigLoader](../../Loader/CumulativeConfigLoader.php). The following example demonstrates this:

``` php
<?php

namespace Acme\Bundle\SomeBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class AcmeSomeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        // load configuration from acme.yml which can be located in any bundle
        $acmeConfig = [];
        $configLoader = new CumulativeConfigLoader(
            'acme_config',
            new YamlCumulativeFileLoader('Resources/config/acme.yml')
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            $acmeConfig = array_merge($acmeConfig, $resource->data);
        }
        $container->setParameter('acme_some.configuration', $acmeConfig);

        // load container configuration
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }
}
```

Initialization
--------------
The `Cumulative Resources` routine need to be initialized before you can use it. It can be done in your application Kernel class. The initialization steps include clearing state of [CumulativeResourceManager](../../CumulativeResourceManager.php), which should be done before constructors of any bundle will be called, and set list of available bundles. The following example shows how it is done in ORO platform:

``` php
<?php

namespace Oro\Bundle\DistributionBundle;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\ConfigCache;

use Oro\Component\Config\CumulativeResourceManager;

abstract class OroKernel extends Kernel
{
    protected function initializeBundles()
    {
        parent::initializeBundles();

        // pass bundles to CumulativeResourceManager
        $bundles       = [];
        foreach ($this->bundles as $name => $bundle) {
            $bundles[$name] = get_class($bundle);
        }
        CumulativeResourceManager::getInstance()->setBundles($bundles);
    }

    public function registerBundles()
    {
        // clear state of CumulativeResourceManager
        CumulativeResourceManager::getInstance()->clear();

        ...
    }

    ...
}
```
Resource Loaders
----------------

As well as `Symfony Config Component` the `Oro Config Component` uses own loader for each type of the resource. Currently the following loaders are implempented:

 - [YAML file loader](../../Loader/YamlCumulativeFileLoader.php) - responsible to load YAML files. Do not provide any normalization or validation of loaded data.
 - ["Foldering" file loader](../../Loader/FolderingCumulativeFileLoader.php) - provides a way to load a configuration file located in a folder conforms some pattern.

Examples
--------

### Load configuration from different file types, for example YAML and XML

``` php
<?php

class AcmeSomeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $acmeConfig = [];
        $configLoader = new CumulativeConfigLoader(
            'acme_config',
            [
                new YamlCumulativeFileLoader('Resources/config/acme.yml')
                new MyXmlCumulativeFileLoader('Resources/config/acme.xml')
            ]
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            $acmeConfig = array_merge($acmeConfig, $resource->data);
        }
    }
}
```

### Load configuration from different files

``` php
<?php

class AcmeSomeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $acmeConfig = [
            'foo' => [],
            'bar' => []
        ];
        $configLoader = new CumulativeConfigLoader(
            'acme_config',
            [
                new YamlCumulativeFileLoader('Resources/config/foo.yml')
                new YamlCumulativeFileLoader('Resources/config/bar.yml')
            ]
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            $acmeConfig[$resource->name] = array_merge($acmeConfig[$resource->name], $resource->data);
        }
    }
}
```

### Load configuration files located in different folders

``` php
<?php

class AcmeSomeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $acmeConfig = [];
        $configLoader = new CumulativeConfigLoader(
            'acme_config',
            new FolderingCumulativeFileLoader(
                '{folder}', // placeholder name
                '\w+',      // regex pattern the folder should conform
                new YamlCumulativeFileLoader('Resources/config/widgets/{folder}/widget.yml')
            )
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            $folderName = basename(dirname($resource->path)); 
            $acmeConfig[$folderName] = $resource->data;
        }
    }
}
```
