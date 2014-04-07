Cumulative Resources
--------------------

This resource type provides a way to load configuration from any bundle without an additional registration of configuration files in each bundle.

Introdution
-----------
Please imagine your bundle need to load configuration from `Resources\config\acme.yml` file located in any other bundle. In other words you need to allow other bundles to provide additional configuration to your bundle. In this case a bundle which need this configuration should do the following steps:

 - Register configuration file loader in a constructor of your bundle. For example:

``` php
<?php

namespace Acme\Bundle\SomeBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class AcmeSomeBundle extends Bundle
{
    public function __construct()
    {
        // register acme.yml as configuration resource
        CumulativeResourceManager::getInstance()->addResourceLoader(
            $this->getName(), // resource group name, in this case it is 'AcmeSomeBundle'
            new YamlCumulativeFileLoader('Resources/config/acme.yml')
        );
    }
}
```
 - Add a code to load configuration in the extension or a compiler pass of your bundle. For example:

``` php
<?php

namespace Acme\Bundle\SomeBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\Loader\CumulativeConfigLoader;

class AcmeSomeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        // load configuration from acme.yml which can be located in any bundle
        $acmeConfig = [];
        $configLoader = new CumulativeConfigLoader($container);
        $resources    = $configLoader->load('AcmeSomeBundle');
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
The `Cumulative Resources` routine need to be initialized before you can use it. It can be done in your application Kernel class. The initialization steps include clearing a state of [CumulativeResourceManager](../../CumulativeResourceManager.php) before constructors of any bundle will be called and set a list of available bundles for this manager. The following example shows how it is done in ORO platform:

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
        // clear a state of CumulativeResourceManager
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

 - registration

``` php
<?php

class AcmeSomeBundle extends Bundle
{
    public function __construct()
    {
        CumulativeResourceManager::getInstance()->addResourceLoader(
            $this->getName(),
            [
                new YamlCumulativeFileLoader('Resources/config/acme.yml')
                new MyXmlCumulativeFileLoader('Resources/config/acme.xml')
            ]
        );
    }
}
```
 - loading

``` php
<?php

class AcmeSomeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $acmeConfig = [];
        $configLoader = new CumulativeConfigLoader($container);
        $resources    = $configLoader->load('AcmeSomeBundle');
        foreach ($resources as $resource) {
            $acmeConfig = array_merge($acmeConfig, $resource->data);
        }
    }
}
```

### Load configuration from different files

 - registration

``` php
<?php

class AcmeSomeBundle extends Bundle
{
    public function __construct()
    {
        CumulativeResourceManager::getInstance()->addResourceLoader(
            $this->getName(),
            [
                new YamlCumulativeFileLoader('Resources/config/foo.yml')
                new YamlCumulativeFileLoader('Resources/config/bar.yml')
            ]
        );
    }
}
```
 - loading

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
        $configLoader = new CumulativeConfigLoader($container);
        $resources    = $configLoader->load('AcmeSomeBundle');
        foreach ($resources as $resource) {
            $acmeConfig[$resource->name] = array_merge($acmeConfig[$resource->name], $resource->data);
        }
    }
}
```

### Load configuration files located in different folders

 - registration

``` php
<?php

class AcmeSomeBundle extends Bundle
{
    public function __construct()
    {
        CumulativeResourceManager::getInstance()->addResourceLoader(
            $this->getName(),
            new FolderingCumulativeFileLoader(
                '{folder}',
                '\w+',
                new YamlCumulativeFileLoader('Resources/config/widgets/{folder}/widget.yml')
            )
        );
    }
}
```
 - loading

``` php
<?php

class AcmeSomeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $acmeConfig = [];
        $configLoader = new CumulativeConfigLoader($container);
        $resources    = $configLoader->load('AcmeSomeBundle');
        foreach ($resources as $resource) {
            $acmeConfig[basename(dirname($resource->path))] = $resource->data;
        }
    }
}
```
