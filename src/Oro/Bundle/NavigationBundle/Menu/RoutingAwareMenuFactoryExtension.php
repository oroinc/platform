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

            $route = $options['route'];
            $newOptions['uri'] = $this->generateUriWithoutFrontendController($options, $params);
            $newOptions['extras']['routes'] = [$route];
            $newOptions['extras']['routesParameters'] = [$route => $params];

            $options = array_merge_recursive($newOptions, $options);
        }

        return $options;
    }

    /**
     * Generates url without frontend controller php file if it's a path (not absolute) url
     * as it will be added later (if needed).
     *
     * @param array $options
     * @param array $params
     * @return string
     */
    private function generateUriWithoutFrontendController(array $options, array $params): string
    {
        $referenceType = !empty($options['routeAbsolute'])
            ? RouterInterface::ABSOLUTE_URL
            : RouterInterface::ABSOLUTE_PATH;

        if ($referenceType === RouterInterface::ABSOLUTE_PATH) {
            $oldBaseURL = $this->router->getContext()->getBaseUrl();
            $this->router->getContext()->setBaseUrl('');
        }

        $url = $this->router->generate($options['route'], $params, $referenceType);

        if ($referenceType === RouterInterface::ABSOLUTE_PATH) {
            $this->router->getContext()->setBaseUrl($oldBaseURL);
        }

        return $url;
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
