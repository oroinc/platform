<?php

namespace Oro\Bundle\NavigationBundle\ContentProvider;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\NavigationBundle\Provider\ConfigurationProvider;
use Oro\Bundle\UIBundle\ContentProvider\AbstractContentProvider;

class NavigationElementsContentProvider extends AbstractContentProvider
{
    /** @var ConfigurationProvider */
    private $configurationProvider;

    /** @var Request */
    protected $request;

    /**
     * @param ConfigurationProvider $configurationProvider
     */
    public function __construct(ConfigurationProvider $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * @param Request|null $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        // TODO set const
        $menuConfig = $this->configurationProvider->getConfiguration('oro_menu_config');
        $elementConfiguration = $menuConfig['oro_navigation_elements'];

        $elements      = array_keys($elementConfiguration);
        $defaultValues = $values = array_map(
            function ($item) {
                return $item['default'];
            },
            $elementConfiguration
        );

        if (null !== $this->request) {
            $attributes = $this->request->attributes;
            $routeName  = $attributes->get('_route') ?: $attributes->get('_master_request_route') ?: '' ;
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'navigationElements';
    }

    /**
     * @param string $element
     * @param string $route
     *
     * @return bool
     */
    protected function hasConfigValue($element, $route)
    {
        // TODO set const
        $menuConfig = $this->configurationProvider->getConfiguration('oro_menu_config');
        $elementConfiguration = $menuConfig['oro_navigation_elements'];
        return isset($elementConfiguration[$element], $elementConfiguration[$element]['routes'][$route]);
    }

    /**
     * @param string $element
     * @param string $route
     *
     * @return null|bool
     */
    protected function getConfigValue($element, $route)
    {
        // TODO set const
        $menuConfig = $this->configurationProvider->getConfiguration('oro_menu_config');
        $elementConfiguration = $menuConfig['oro_navigation_elements'];
        return $this->hasConfigValue($element, $route) ? (bool)$elementConfiguration[$element]['routes'][$route] : null;
    }
}
