<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Symfony\Component\HttpFoundation\Request;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;

class RouteContextConfigurator implements ContextConfiguratorInterface
{
    const PARAM_ROUTE_NAME = 'route_name';

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
        $context->getDataResolver()
            ->setOptional([self::PARAM_ROUTE_NAME])
            ->setAllowedTypes([self::PARAM_ROUTE_NAME => ['string', 'null']])
            ->setNormalizers(
                [
                    self::PARAM_ROUTE_NAME => function ($options, $route) {
                        if (null === $route && $this->request) {
                            $route = $this->request->attributes->get('_route');
                            if (null === $route) {
                                $route = $this->request->attributes->get('_master_request_route');
                            }
                        }

                        return $route;
                    }
                ]
            );
    }
}
