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
    /** @var RouterInterface */
    private $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function buildItem(ItemInterface $item, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildOptions(array $options = [])
    {
        if (!empty($options['route']) && $this->getExtraOption($options, 'isAllowed', true)) {
            $params = $this->getOption($options, 'routeParameters', []);
            $referenceType = !empty($options['routeAbsolute'])
                ? RouterInterface::ABSOLUTE_URL
                : RouterInterface::ABSOLUTE_PATH;

            $route = $options['route'];
            $newOptions['uri'] = $this->router->generate($route, $params, $referenceType);
            $newOptions['extras']['routes'] = [$route];
            $newOptions['extras']['routesParameters'] = [$route => $params];

            $options = array_merge_recursive($newOptions, $options);
        }

        return $options;
    }

    /**
     * @param array  $options
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    private function getOption(array $options, $key, $default = null)
    {
        if (array_key_exists($key, $options)) {
            return $options[$key];
        }

        return $default;
    }

    /**
     * @param array  $options
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    private function getExtraOption(array $options, $key, $default = null)
    {
        if (array_key_exists('extras', $options)) {
            $extras = $options['extras'];
            if (array_key_exists($key, $extras)) {
                return $extras[$key];
            }
        }

        return $default;
    }
}
