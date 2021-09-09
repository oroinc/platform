<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\Factory\ExtensionInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Adds uri, extras/routes and extras/routesParameters for allowed menu items.
 */
class RoutingAwareMenuFactoryExtension implements ExtensionInterface
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function buildItem(ItemInterface $item, array $options): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildOptions(array $options): array
    {
        if (!empty($options['route']) && ($options['extras']['isAllowed'] ?? true)) {
            $params = $options['routeParameters'] ?? [];
            $referenceType = !empty($options['routeAbsolute'])
                ? RouterInterface::ABSOLUTE_URL
                : RouterInterface::ABSOLUTE_PATH;

            $route = (string) $options['route'];
            $newOptions['uri'] = $this->router->generate($route, $params, $referenceType);
            $newOptions['extras']['routes'] = [$route];
            $newOptions['extras']['routesParameters'] = [$route => $params];

            $options = array_merge_recursive($newOptions, $options);
        }

        return $options;
    }
}
