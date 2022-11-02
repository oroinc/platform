<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\RouterInterface;

/**
 * Actions that generates url and assigns to the specified attribute.
 */
class AssignUrl extends AbstractAction
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var PropertyPath
     */
    protected $urlAttribute;

    public function __construct(ContextAccessor $contextAccessor, RouterInterface $router)
    {
        parent::__construct($contextAccessor);

        $this->router = $router;
    }

    /**
     * {@inheritDoc}
     */
    protected function executeAction($context)
    {
        $this->contextAccessor->setValue($context, $this->urlAttribute, $this->getUrl($context));
    }

    protected function getUrl($context)
    {
        $route = $this->getRoute($context);
        $routeParameters = $this->getRouteParameters($context);

        return $this->router->generate($route, $routeParameters);
    }

    /**
     * Allowed options:
     *  - url (optional) - direct URL that will be used to perform redirect
     *  - route (optional) - route used to generate url
     *  - route_parameters (optional) - route parameters
     *
     * {@inheritDoc}
     */
    public function initialize(array $options)
    {
        if (empty($options['route'])) {
            throw new InvalidParameterException('Route parameter must be specified');
        }

        if (!empty($options['route_parameters']) && !is_array($options['route_parameters'])) {
            throw new InvalidParameterException('Route parameters must be an array');
        }
        if (empty($options['attribute'])) {
            throw new InvalidParameterException('Attribute parameters is required');
        }

        $this->urlAttribute = $options['attribute'];
        $this->options = $options;

        return $this;
    }

    /**
     * @param mixed $context
     * @return string
     */
    protected function getRoute($context): string
    {
        return !empty($this->options['route'])
            ? (string) $this->contextAccessor->getValue($context, $this->options['route'])
            : '';
    }

    /**
     * @param mixed $context
     * @return array
     */
    protected function getRouteParameters($context)
    {
        $routeParameters = $this->getOption($this->options, 'route_parameters', array());

        foreach ($routeParameters as $name => $value) {
            $routeParameters[$name] = $this->contextAccessor->getValue($context, $value);
        }

        return $routeParameters;
    }
}
