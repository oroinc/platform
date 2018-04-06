<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\RouterInterface;

class Redirect extends AssignUrl
{
    /**
     * @param ContextAccessor $contextAccessor
     * @param RouterInterface $router
     * @param string $redirectPath
     */
    public function __construct(ContextAccessor $contextAccessor, RouterInterface $router, $redirectPath)
    {
        parent::__construct($contextAccessor, $router);

        $this->urlAttribute = $redirectPath;
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
        if (empty($options['url']) && empty($options['route'])) {
            throw new InvalidParameterException('Either url or route parameter must be specified');
        }

        if (!empty($options['route_parameters']) && !is_array($options['route_parameters'])) {
            throw new InvalidParameterException('Route parameters must be an array');
        }

        $this->urlAttribute = new PropertyPath($this->urlAttribute);
        $this->options = $options;

        return $this;
    }

    /**
     * @param mixed $context
     * @return string|null
     */
    protected function getUrl($context)
    {
        if ($this->getRoute($context)) {
            $url = parent::getUrl($context);
        } else {
            $url = !empty($this->options['url'])
                ? $this->contextAccessor->getValue($context, $this->options['url'])
                : null;
        }

        return $url;
    }
}
