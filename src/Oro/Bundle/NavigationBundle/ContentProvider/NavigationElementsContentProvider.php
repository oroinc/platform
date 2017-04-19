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
        $navigationElements = $this->configurationProvider
            ->getConfiguration(ConfigurationProvider::NAVIGATION_ELEMENTS_KEY);

        $elements = array_keys($navigationElements);
        $defaultValues = $values = array_map(
            function ($item) {
                return $item['default'];
            },
            $navigationElements
        );

        if (null !== $this->request) {
            $attributes = $this->request->attributes;

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
        $navigationElements = $this->configurationProvider
            ->getConfiguration(ConfigurationProvider::NAVIGATION_ELEMENTS_KEY);

        return isset($navigationElements[$element], $navigationElements[$element]['routes'][$route]);
    }

    /**
     * @param string $element
     * @param string $route
     *
     * @return null|bool
     */
    protected function getConfigValue($element, $route)
    {
        $navigationElements = $this->configurationProvider
            ->getConfiguration(ConfigurationProvider::NAVIGATION_ELEMENTS_KEY);

        return $this->hasConfigValue($element, $route) ? (bool)$navigationElements[$element]['routes'][$route] : null;
    }
}
