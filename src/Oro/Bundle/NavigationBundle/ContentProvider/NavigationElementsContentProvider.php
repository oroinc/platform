<?php

namespace Oro\Bundle\NavigationBundle\ContentProvider;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\UIBundle\ContentProvider\AbstractContentProvider;

class NavigationElementsContentProvider extends AbstractContentProvider
{
    /** @var array */
    protected $configuration;

    /** @var Request */
    protected $request;

    /**
     * @param array $configuration array of current configuration comes from navigation.yml configs.
     *                             Example: [
     *                                 'pinBar' => [
     *                                    'routes'  => ['page_with_pinbar' => true, 'page_without_pinbar' => false]
     *                                    'default' => true
     *                             ]
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
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
        $elements      = array_keys($this->configuration);
        $defaultValues = $values = array_map(
            function ($item) {
                return $item['default'];
            },
            $this->configuration
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
        return isset($this->configuration[$element], $this->configuration[$element]['routes'][$route]);
    }

    /**
     * @param string $element
     * @param string $route
     *
     * @return null|bool
     */
    protected function getConfigValue($element, $route)
    {
        return $this->hasConfigValue($element, $route) ? (bool)$this->configuration[$element]['routes'][$route] : null;
    }
}
