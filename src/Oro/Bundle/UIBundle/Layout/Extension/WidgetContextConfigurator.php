<?php

namespace Oro\Bundle\UIBundle\Layout\Extension;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

class WidgetContextConfigurator implements ContextConfiguratorInterface
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
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
            ->setAllowedTypes(['widget_container' => ['string', 'null']]);

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
