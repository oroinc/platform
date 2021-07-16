<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;

/**
 * Adds route_name and is_xml_http_request to layout context
 */
class RouteContextConfigurator implements ContextConfiguratorInterface
{
    /** @var RequestStack */
    protected $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
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
                        $request = $this->requestStack->getCurrentRequest();
                        if (null === $value && $request) {
                            $value = $request->attributes->get('_route');
                            if (null === $value) {
                                $value = $request->attributes->get('_master_request_route');
                            }
                        }

                        return $value;
                    },
                    'is_xml_http_request' => function (Options $options, $value) {
                        if (null === $value) {
                            $request = $this->requestStack->getCurrentRequest();
                            if ($request) {
                                $value = $request->isXmlHttpRequest();
                            }
                        }

                        return (bool) $value;
                    },
                ]
            )
            ->setAllowedTypes('route_name', ['string', 'null'])
            ->setAllowedTypes('is_xml_http_request', ['bool']);
    }
}
