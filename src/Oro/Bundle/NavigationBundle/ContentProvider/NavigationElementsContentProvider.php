<?php

namespace Oro\Bundle\NavigationBundle\ContentProvider;

use Oro\Bundle\NavigationBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns navigation elements.
 */
class NavigationElementsContentProvider implements ContentProviderInterface
{
    /** @var ConfigurationProvider */
    private $configurationProvider;

    /** @var RequestStack */
    private $requestStack;

    public function __construct(ConfigurationProvider $configurationProvider, RequestStack $requestStack)
    {
        $this->configurationProvider = $configurationProvider;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        $navigationElements = $this->configurationProvider->getNavigationElements();

        $elements = array_keys($navigationElements);
        $defaultValues = $values = array_map(
            function ($item) {
                return $item['default'];
            },
            $navigationElements
        );

        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request) {
            $attributes = $request->attributes;

            $routeName  = $attributes->get('_route');
            if (!$routeName) {
                $routeName = $attributes->get('_master_request_route') ?: '' ;
            }

            $hasErrors  = $attributes->get('exception');

            foreach ($elements as $elementName) {
                $value = $defaultValues[$elementName] && (!$hasErrors);
                if ($this->hasConfigValue($elementName, $routeName)) {
                    $value = $this->getConfigValue($elementName, $routeName) && (!$hasErrors);
                }

                $values[$elementName] = $value;
            }
        }

        return $values;
    }

    /**
     * @param string $element
     * @param string $route
     *
     * @return bool
     */
    private function hasConfigValue($element, $route)
    {
        $navigationElements = $this->configurationProvider->getNavigationElements();

        return isset($navigationElements[$element]['routes'][$route]);
    }

    /**
     * @param string $element
     * @param string $route
     *
     * @return bool|null
     */
    private function getConfigValue($element, $route)
    {
        $navigationElements = $this->configurationProvider->getNavigationElements();

        return isset($navigationElements[$element]['routes'][$route])
            ? (bool)$navigationElements[$element]['routes'][$route]
            : null;
    }
}
