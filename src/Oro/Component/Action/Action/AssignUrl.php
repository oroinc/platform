<?php

namespace Oro\Component\Action\Action;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;

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

    /**
     * @param ContextAccessor $contextAccessor
     * @param RouterInterface $router
     */
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
            throw new InvalidParameterException('Attribiute parameters is required');
        }

        $this->urlAttribute = $options['attribute'];
        $this->options = $options;

        return $this;
    }

    /**
     * @param mixed $context
     * @return string|null
     */
    protected function getRoute($context)
    {
        return !empty($this->options['route'])
            ? $this->contextAccessor->getValue($context, $this->options['route'])
            : null;
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
