<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\Factory\ExtensionInterface;
use Knp\Menu\ItemInterface;

use Symfony\Component\Routing\RouterInterface;

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
        if (!empty($options['route']) && $this->getOptionValue($options, ['extras', 'isAllowed'], true)) {
            $params = $this->getOptionValue($options, ['routeParameters'], []);

            $newOptions['uri'] = $this->router->generate($options['route'], $params, !empty($options['routeAbsolute']));
            $newOptions['extras']['routes'] = [$options['route']];
            $newOptions['extras']['routesParameters'] = [$options['route'] => $params];

            $options = array_merge_recursive($newOptions, $options);
        }

        return $options;
    }

    /**
     * @param array $options
     * @param array $keys
     * @param mixed $default
     *
     * @return mixed
     */
    private function getOptionValue(array $options, array $keys, $default = null)
    {
        $key = array_shift($keys);
        if (!array_key_exists($key, $options)) {
            return $default;
        }

        return $keys ? $this->getOptionValue($options[$key], $keys, $default) : $options[$key];
    }
}
