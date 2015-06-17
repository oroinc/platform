Oro Routing Component
=====================

`Oro Routing Component` provides additional flexibility for the [Symfony Routing Component](http://symfony.com/doc/current/components/routing/introduction.html).

Supported Features
------------------

- Provides implementation of a routing loader for collecting routing definition from all bundles.
- Allows to change a priority of a route through `priority` option.
- Provides a way to easily create own resolvers for routes based on route options.


Configuration
-------------

It is supposed that your application is built around [Symfony Framework](http://symfony.com/), but this component can be used without it as well.

At first, you need to register the cumulative loader which allows you to load routing definitions from all your bundles automatically.

``` yaml
services:
    acme.routing_loader:
        class: Oro\Component\Routing\Loader\CumulativeRoutingFileLoader
        arguments:
            - @kernel
            - @acme.routing_options_resolver
            - [Resources/config/acme/routing.yml]
            - acme_auto
        calls:
            - [setResolver, [@routing.resolver]]
        tags:
            - { name: routing.loader }

    acme.routing_options_resolver:
        class: Oro\Component\Routing\Resolver\ChainRouteOptionsResolver
        public: false
```

Here we also have registered the chain route options resolver service which allows to add resolvers from any bundle. There are several ways how to allow a bundle to register own route options resolver in the chain resolver, but most common way is to use DI container tags. The following example shows implementation a compiler pass for DI container to load tagged resolvers:

``` php
<?php

namespace Acme\Bundle\AppBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RoutingOptionsResolverPass implements CompilerPassInterface
{
    const CHAIN_RESOLVER_SERVICE = 'acme.routing_options_resolver';
    const RESOLVER_TAG_NAME = 'routing.options_resolver';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::CHAIN_RESOLVER_SERVICE)) {
            return;
        }

        // find resolvers
        $resolvers      = [];
        $taggedServices = $container->findTaggedServiceIds(self::RESOLVER_TAG_NAME);
        foreach ($taggedServices as $id => $attributes) {
            $priority               = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $resolvers[$priority][] = new Reference($id);
        }
        if (empty($resolvers)) {
            return;
        }

        // sort by priority and flatten
        ksort($resolvers);
        $resolvers = call_user_func_array('array_merge', $resolvers);

        // register
        $chainResolverDef = $container->getDefinition(self::CHAIN_RESOLVER_SERVICE);
        foreach ($resolvers as $resolver) {
            $chainResolverDef->addMethodCall('addResolver', [$resolver]);
        }
    }
}
```

Now you need to register this compiler pass:

``` php
<?php

namespace Acme\Bundle\AppBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Acme\Bundle\AppBundle\DependencyInjection\Compiler\RoutingOptionsResolverPass;

class OroDistributionBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RoutingOptionsResolverPass());
    }
}
```

The last thing you need to do is to register a root routing resource for your application in `app/config/routing.yml`:

``` yaml
acme_auto_routing:
    resource: .
    type: acme_auto
```

The configuration of the `Oro Routing Component` is finished.


Change a route priority
-----------------------

In Symfony if several routes match the same URL the earlier route always win. The routes order registered by the cumulative loader depends on the order of bundles. But sometimes you may need to change this order.

To achieve this, the `priority` option was introduced. By default all routes have zero priority. If you need to move a route up, set lesser number for the `priority` option. For example the following route will be moved at the top of the route list:

``` yaml
acme_product:
    resource:     "@AcmeProductBundle/Controller"
    type:         annotation
    prefix:       /product
    options:
        priority: -1
```

Create own route options resolver
---------------------------------

At the first implement your resolver. It must implement [RouteOptionsResolverInterface](/Resolver/RouteOptionsResolverInterface.php). For example:

``` php
<?php

namespace Acme\Bundle\ProductBundle\Routing;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;

class MyRouteOptionsResolver implements RouteOptionsResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routeCollectionAccessor)
    {
        // Add your logic here
    }
}
```

Next register it in DI container with a tag supported by the compiler pass implemented above. For example:

``` yaml
services:
    acme_product.routing.options_resolver.my:
        class: Acme\Bundle\ProductBundle\Routing\MyRouteOptionsResolver
        public: false
        tags:
            - { name: routing.options_resolver }

```
