## Config management ##
### Controller ###
You can access different Oro settings using different scopes.

**Note:** Currently, only `oro_config.user` scope implemented.

``` php
<?php
$config = $this->get('oro_config.user');
$value  = $config->get('oro_anybundle.anysetting');
```

To define settings inside your bundle you can use `SettingsBuilder` helper class.

YourBundle\DependencyInjection\Configuration.php:

``` php
<?php
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

// ...

public function getConfigTreeBuilder()
{
    $builder = new TreeBuilder();
    $root    = $builder
        ->root('oro_mybundle')
        ->children()
            // ...
        ->end();

     SettingsBuilder::append($root, array(
        'settingname' => array(
            'value' => true,
            'type'  => 'boolean',
        ),
        'anothersetting' => array(
            'value' => 10,
        ),
    ));

    return $builder;
}
```

`type` above could be `scalar` (which is default), `boolean` or `array`.
This call will append additional nodes to bundle config tree.

After tree will be processed in Extension class, need to pass configuration data to container.
Array with `settings` - key should be set using `Containerbuilder#prependExtensionConfiguration method`.

**Example:**
``` php
     public function load(array $configs, ContainerBuilder $container)
     {
         // ....
         $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
         // ...
     }
```

### View ###

```
{% set format = oro_config_value('oro_anybundle.anysetting') %}
```
