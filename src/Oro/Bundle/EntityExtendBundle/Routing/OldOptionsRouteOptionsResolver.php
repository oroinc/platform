<?php

namespace Oro\Bundle\EntityExtendBundle\Routing;

use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
/**
 * As FOSRestBundle v1.7.1 generates a plural path for OPTIONS routes,
 * we need to add a single path to avoid BC break.
 * The single path is marked as deprecated.
 *
 * @deprecated since 1.8. Will be removed in 2.0
 */
class OldOptionsRouteOptionsResolver implements RouteOptionsResolverInterface
{
    protected $configManager;

    protected $aliases = [];

    public function __construct(ConfigManager $configManager, EntityAliasResolver $entityAliasLoader)
    {
        $this->configManager = $configManager;
        $configs   = $this->configManager->getConfigs('extend', null, true);
        foreach ($configs as $config) {
            if ($config->is('is_extend') && $config->is('owner', ExtendScope::OWNER_CUSTOM)) {
//                $aliase = $entityAliasLoader->getAlias($config->getId()->getClassName());
                $this->aliases[] = $entityAliasLoader->getAlias($config->getId()->getClassName());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        foreach ($this->aliases as $aliase) {
            $path = '/api/rest/{version}/'.$aliase.'/{id}.{_format}';
            $localRoute = $routes->get($aliase);
            if (!$localRoute) {
                $apiRouteGet = $routes->get('oro_rest_api_get');
                $newRoute = $routes->cloneRoute($apiRouteGet);
                $newRoute->setPath($path);
//                $newRoute-getOpt
                $routes->append($aliase, $newRoute);

//                $localRoute = new Route();

//                $routes->
            }
        }
//        if ($route->getPath() === '/magento/order/view/{id}') {
//            $singleRoute = $routes->cloneRoute($route);
//            $singleRoute->setPath('/magentoorder/view/{id}');
//            $routes->append('orocrm_magentoorder_view', $singleRoute);
//        }
//
//        if (!in_array('GET', $route->getMethods(), true)) {
//            return;
//        }
//
//        if ($route->getPath() === '/api/rest/{version}/carts/{id}.{_format}') {
//            $singleRoute = $routes->cloneRoute($route);
//            $singleRoute->setPath('/api/rest/{version}/magentocarts/{id}.{_format}');
//            $routes->append('oro_api_get_magentocarts', $singleRoute);
//        }
//
//        if ($route->getPath() === '/api/rest/{version}/orders/{id}.{_format}') {
//            $singleRoute = $routes->cloneRoute($route);
//            $singleRoute->setPath('/api/rest/{version}/magentoorders/{id}.{_format}');
//            $routes->append('oro_api_get_magentoorders', $singleRoute);
//        }
    }
}
