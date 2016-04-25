Configuration Merger
====================

This class provides a way to merge configurations with equal name from any configuration groups and way to extend one
configuration from other configuration. Also exists mechanism to replace some nodes of original configuration by nodes
of configuration which will be extended from this config. For this case you need to set node `replace` with list of
nodes, which you want to replace, on the same level of this nodes.

Initialization
--------------

For creating new instance of merger you need list of some keys. It will be used as sorting order for merging all
configuration from groups which have equal name.

``` php
<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfigurationPass implements CompilerPassInterface
{
    /** {@inheritDoc} */
    public function process(ContainerBuilder $container)
    {
        ...
        $bundles = $this->container->getParameter('kernel.bundles');

        $merger = new ConfigurationMerger($bundles);
        ...
    }
}
...
```

Using example
-------------

Please imagine that you need to load configurations from `Resources\config\acme.yml` file located in any bundle in your
application and merge them to final configurations. For example one bundle, which will be loaded last, override some
part of configuration from other bundle. All process to load this configurations shows below.

``` php
<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class ConfigurationPass implements CompilerPassInterface
{
    /** {@inheritDoc} */
    public function process(ContainerBuilder $container)
    {
        $configLoader = new CumulativeConfigLoader(
            'acme_config',
            new YamlCumulativeFileLoader('Resources/config/acme.yml')
        );

        $resources = $configLoader->load($container);
        $configs = [];

        foreach ($resources as $resource) {
            $configs[$resource->bundleClass] = $resource->data;
        }

        $bundles = $this->container->getParameter('kernel.bundles');

        $merger = new ConfigurationMerger($bundles);
        $configs = $merger->mergeConfiguration($configs);
        ...
    }
}
```

Examples
--------

### Merge configurations with the same name from two bundles (use append strategy)

Order of bundles loading: `FirstBundle`, `SecondBundle`.

``` yaml
# Acme\Bundle\FirstBundle\Resources\config\acme.yml

acme_config:
    param: value
    array_param:
        sub_array_param1: value1
        sub_array_param2: value2
```

``` yaml
# Acme\Bundle\SecondBundle\Resources\config\acme.yml

acme_config:
    param: replaced_value
    array_param:
        sub_array_param3: value3
```

Result:
``` yaml
acme_config:
    param: replaced_value
    array_param:
        sub_array_param1: value1
        sub_array_param2: value2
        sub_array_param3: value3
```

### Extends one configuration from other configuration (use append strategy)
``` yaml
# Acme\Bundle\DemoBundle\Resources\config\acme.yml

acme_config_base:
    param: value
    array_param:
        sub_array_param1: value1
        sub_array_param2: value2

acme_config:
    extends: acme_config_base
    new_param: new_value
    array_param:
        sub_array_param3: value3
```

Result:
``` yaml
acme_config_base:
    param: value
    array_param:
        sub_array_param1: value1
        sub_array_param2: value2

acme_config:
    param: value
    array_param:
        sub_array_param1: value1
        sub_array_param2: value2
        sub_array_param3: value3
    new_param: new_value
```

### Merge configurations with the same name from two bundles and extends one configuration from other configuration (use append strategy)

Order of bundles loading: `FirstBundle`, `SecondBundle`.

``` yaml
# Acme\Bundle\FirstBundle\Resources\config\acme.yml

acme_config_base:
    param: value
    array_param:
        sub_array_param1: value1
        sub_array_param2: value2

acme_config:
    extends: acme_config_base
    new_param: new_value
    array_param:
        sub_array_param4: value4
```

``` yaml
# Acme\Bundle\SecondBundle\Resources\config\acme.yml

acme_config_base:
    param: replaced_value
    array_param:
        sub_array_param3: value3

```

Result:
``` yaml
acme_config_base:
    param: replaced_value
    array_param:
        sub_array_param1: value1
        sub_array_param2: value2
        sub_array_param3: value3

acme_config:
    param: replaced_value
    array_param:
        sub_array_param1: value1
        sub_array_param2: value2
        sub_array_param3: value3
        sub_array_param4: value4
    new_param: new_value
```

### Extends one configuration from other configuration (use append and replace strategies)
``` yaml
# Acme\Bundle\DemoBundle\Resources\config\acme.yml

acme_config_base:
    param: value
    array_param:
        sub_array_param1: value1
        sub_array_param2: value2

acme_config:
    extends: acme_config_base
    replace: [array_param]
    new_param: new_value
    array_param:
        sub_array_param3: value3
```

Result:
``` yaml
acme_config_base:
    param: value
    array_param:
        sub_array_param1: value1
        sub_array_param2: value2

acme_config:
    param: value
    array_param:
        sub_array_param3: value3
    new_param: new_value
```

### Merge configurations with the same name from two bundles and extends one configuration from other configuration (use append and replace strategy)

Order of bundles loading: `FirstBundle`, `SecondBundle`.

``` yaml
# Acme\Bundle\FirstBundle\Resources\config\acme.yml

acme_config_base:
    param: value
    array_param:
        sub_array_param1: value1
        sub_array_param2: value2

acme_config:
    extends: acme_config_base
    replace: [array_param]
    new_param: new_value
    array_param:
        sub_array_param4: value4
```

``` yaml
# Acme\Bundle\SecondBundle\Resources\config\acme.yml

acme_config_base:
    param: replaced_value
    array_param:
        sub_array_param3: value3

```

Result:
``` yaml
acme_config_base:
    param: replaced_value
    array_param:
        sub_array_param1: value1
        sub_array_param2: value2
        sub_array_param3: value3

acme_config:
    param: replaced_value
    array_param:
        sub_array_param4: value4
    new_param: new_value
```
