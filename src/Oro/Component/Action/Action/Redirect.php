<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\RouterInterface;

/**
 * Redirect to URL.
 * If new_tab parameter passed URL will be open in new browser tab, otherwise redirect will be processed in same tab.
 *
 * Usage:
 *
 * @redirect:
 *     route: 'oro_acme_entity_view'
 *     route_parameters:
 *         id: $.id
 *
 * OR
 *
 * @redirect:
 *     route: 'oro_acme_entity_view'
 *     route_parameters:
 *         id: $.id
 *     new_tab: true
 */
class Redirect extends AssignUrl
{
    const NEW_TAB_OPTION = 'new_tab';

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
     * {@inheritDoc}
     */
    protected function executeAction($context)
    {
        parent::executeAction($context);
        $newTab = array_key_exists(self::NEW_TAB_OPTION, $this->options) ? $this->options[self::NEW_TAB_OPTION] : false;
        if ($newTab === true) {
            $this->contextAccessor->setValue($context, new PropertyPath('newTab'), $newTab);
        }
    }

    /**
     * Allowed options:
     *  - url (optional) - direct URL that will be used to perform redirect
     *  - route (optional) - route used to generate url
     *  - route_parameters (optional) - route parameters
     *  - new_tab (optional) - mark should be URL open in new browser tab
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
