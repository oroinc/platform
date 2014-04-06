Oro Config Component
====================

`Oro Config Component` in additional to `Symfony Config Component` provides the infrastructure for loading configurations from different data sources and optionally monitoring these data sources for changes.

Cumulative Configuration
------------------------

The cumulative configuration provides a way to load configuration from any bundle without an additional registration of configuration files in each bundle.
For example you may need to load configuration from `Resources\config\acme.yml` file located in any bundle. In this case a bundle which need this configuration should do the following:

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

However to make this works, you have to initialize `Oro Config Component`. It can be done in your application Kernel class. The following example shows how it is done in ORO platform:

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
