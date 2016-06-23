Oro Routing Component
=====================

`Oro Routing Component` provides additional flexibility for the [Symfony Routing Component](http://symfony.com/doc/current/components/routing/introduction.html).

Supported Features
------------------

- Provides implementation of a routing loader for collecting routing definition from all bundles.
- Allows to change a priority of a route through `priority` option.
- Provides a way to easily create own resolvers for routes based on route options.
- Allows to hide a route using `hidden` option.


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

class AcmeAppBundle extends Bundle
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

To achieve this, the `priority` option was introduced. By default all routes have zero priority. If you need to move a route up, set bigger number for the `priority` option. For example the following route will be moved at the top of the route list:

``` yaml
acme_product:
    resource:     "@AcmeProductBundle/Controller"
    type:         annotation
    prefix:       /product
    options:
        priority: 1
```

Please note the bigger the priority number is, the sooner the route will be checked.

Create own route options resolver
---------------------------------

At the first implement your resolver. It must implement [RouteOptionsResolverInterface](./Resolver/RouteOptionsResolverInterface.php). For example:

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
    public function resolve(Route $route, RouteCollectionAccessor $routes)
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

Hide routes
-----------

Sometime you need to hide some route from Symfony URL Matcher, but keep it available in Symfony URL Generator. For example if you have same common route for some kind of entities and you use a route options resolver to create routes for concrete entities based on the common route.

To enable this feature you need to override some services in DI container:

``` php
<?php

namespace Acme\Bundle\AppBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class HiddenRoutesPass implements CompilerPassInterface
{
    const MATCHER_DUMPER_CLASS_PARAM    = 'router.options.matcher_dumper_class';
    const EXPECTED_MATCHER_DUMPER_CLASS = 'Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper';
    const NEW_MATCHER_DUMPER_CLASS      = 'Oro\Component\Routing\Matcher\PhpMatcherDumper';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter(self::MATCHER_DUMPER_CLASS_PARAM)) {
            $newClass = $this->getNewRoutingMatcherDumperClass(
                $container->getParameter(self::MATCHER_DUMPER_CLASS_PARAM)
            );
            if ($newClass) {
                $container->setParameter(self::MATCHER_DUMPER_CLASS_PARAM, $newClass);
            }
        }
    }

    /**
     * @param string $currentClass
     *
     * @return string|null
     */
    protected function getNewRoutingMatcherDumperClass($currentClass)
    {
        return self::EXPECTED_MATCHER_DUMPER_CLASS === $currentClass
            ? self::NEW_MATCHER_DUMPER_CLASS
            : null;
    }
}
```

``` php
<?php

namespace Acme\Bundle\AppBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Acme\Bundle\AppBundle\DependencyInjection\Compiler\HiddenRoutesPass;

class AcmeAppBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new HiddenRoutesPass());
    }
}
```

Now to hide any route just set `hidden` option to `true` for it.

Here is an example of a route options resolver where this feature can be helpful:

``` php
<?php

namespace Acme\Bundle\ProductBundle\Routing;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;

class DictionaryEntityRouteOptionsResolver implements RouteOptionsResolverInterface
{
    const ROUTE_GROUP = 'dictionary_entity';
    const ENTITY_ATTRIBUTE = 'dictionary';
    const ENTITY_PLACEHOLDER = '{dictionary}';

    /** @var array */
    private $supportedEntities;

    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        if ($route->getOption('group') !== self::ROUTE_GROUP) {
            return;
        }

        if ($this->hasAttribute($route, self::ENTITY_PLACEHOLDER)) {
            // generate routes for concrete entities
            $entities = $this->getSupportedEntities();
            if (!empty($entities)) {
                $this->adjustRoutes($route, $routes, $entities);
            }
            $route->setRequirement(self::ENTITY_ATTRIBUTE, '\w+');

            // mark the common route as hidden
            $route->setOption('hidden', true);
        }
    }

    /**
     * @return string[]
     */
    protected function getSupportedEntities()
    {
        if (null === $this->supportedEntities) {
            $entities = ... get supported entities ...

            $this->supportedEntities = [];
            foreach ($entities as $className) {
                $pluralAlias = ... get entity plural alias ...
                $this->supportedEntities[] = $pluralAlias;
            }
        }

        return $this->supportedEntities;
    }

    /**
     * @param Route                   $route
     * @param RouteCollectionAccessor $routes
     * @param string[]                $entities
     */
    protected function adjustRoutes(Route $route, RouteCollectionAccessor $routes, $entities)
    {
        $routeName = $routes->getName($route);

        foreach ($entities as $pluralAlias) {
            $existingRoute = $routes->getByPath(
                str_replace(self::ENTITY_PLACEHOLDER, $pluralAlias, $route->getPath()),
                $route->getMethods()
            );
            if ($existingRoute) {
                // move existing route before the common route
                $existingRouteName = $routes->getName($existingRoute);
                $routes->remove($existingRouteName);
                $routes->insert($existingRouteName, $existingRoute, $routeName, true);
            } else {
                // add an additional strict route based on the common route and current entity
                $strictRoute = $routes->cloneRoute($route);
                $strictRoute->setPath(
                    str_replace(self::ENTITY_PLACEHOLDER, $pluralAlias, $strictRoute->getPath())
                );
                $strictRoute->setDefault(self::ENTITY_ATTRIBUTE, $pluralAlias);
                $routes->insert(
                    $routes->generateRouteName($routeName),
                    $strictRoute,
                    $routeName,
                    true
                );
            }
        }
    }

    /**
     * Checks if a route has the given attribute
     *
     * @param Route  $route
     * @param string $placeholder
     *
     * @return bool
     */
    protected function hasAttribute(Route $route, $placeholder)
    {
        return false !== strpos($route->getPath(), $placeholder);
    }
}
```

The common route can be registered in `routing.yml` file, for example:

``` yaml
acme_dictionary_api:
    resource: "@AcmeProductBundle/Controller/Api/Rest/DictionaryController.php"
    type:         rest
    prefix:       api/rest/{version}
    requirements:
        version:  latest|v1
        _format:  json
    defaults:
        version:  latest
    options:
        group: dictionary_entity
```
