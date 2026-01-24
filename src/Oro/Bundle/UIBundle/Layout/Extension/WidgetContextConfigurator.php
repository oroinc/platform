<?php

namespace Oro\Bundle\UIBundle\Layout\Extension;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;

/**
 * Configures layout context with widget-specific parameters.
 *
 * Extracts widget container and widget ID from the HTTP request and makes them available
 * in the layout context. This allows layouts to be aware of whether they are being rendered
 * within a widget container and to access the widget's unique identifier.
 */
class WidgetContextConfigurator implements ContextConfiguratorInterface
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    #[\Override]
    public function configureContext(ContextInterface $context)
    {
        $request = $this->requestStack->getCurrentRequest();

        $context->getResolver()
            ->setDefaults(
                [
                    'widget_container' => function (Options $options, $value) use ($request) {
                        if (null === $value && $request) {
                            $value = $request->query->get('_widgetContainer')
                                ?: $request->request->get('_widgetContainer');
                        }

                        return $value;
                    }
                ]
            )
            ->setAllowedTypes('widget_container', ['string', 'null']);

        $context->data()->setDefault(
            'widget_id',
            function () use ($request) {
                if (!$request) {
                    throw new \BadMethodCallException('The request expected.');
                }

                return $request->query->get('_wid') ?: $request->request->get('_wid');
            }
        );
    }
}
