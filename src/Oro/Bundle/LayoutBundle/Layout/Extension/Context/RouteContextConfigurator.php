<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension\Context;

use Symfony\Component\HttpFoundation\Request;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;

// TODO cover by test
class RouteContextConfigurator implements ContextConfiguratorInterface
{
    const PARAM_NAME = 'routeName';

    /** @var Request|null */
    protected $request;

    /**
     * Synchronized DI method call, sets current request for further usage
     *
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * Sets current request route name into layout context
     *
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getDataResolver()->setOptional([self::PARAM_NAME]);
        $context->set(self::PARAM_NAME, $this->request ? $this->request->get('_route') : null);
    }
}
