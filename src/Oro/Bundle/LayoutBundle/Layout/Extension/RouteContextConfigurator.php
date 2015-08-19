<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Options;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;

class RouteContextConfigurator implements ContextConfiguratorInterface
{
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
        $context->getResolver()
            ->setDefaults(
                [
                    'route_name' => function (Options $options, $value) {
                        if (null === $value && $this->request) {
                            $value = $this->request->attributes->get('_route');
                            if (null === $value) {
                                $value = $this->request->attributes->get('_master_request_route');
                            }
                        }

                        return $value;
                    }
                ]
            )
            ->setAllowedTypes(['route_name' => ['string', 'null']]);
    }
}
