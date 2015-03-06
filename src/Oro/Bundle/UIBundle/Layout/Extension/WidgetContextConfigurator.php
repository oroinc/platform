<?php

namespace Oro\Bundle\UIBundle\Layout\Extension;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Options;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;

class WidgetContextConfigurator implements ContextConfiguratorInterface
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
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setDefaults(
                [
                    'widget_container' => function (Options $options, $value) {
                        if (null === $value && $this->request) {
                            $value = $this->request->query->get('_widgetContainer')
                                ?: $this->request->request->get('_widgetContainer');
                        }

                        return $value;
                    }
                ]
            )
            ->setAllowedTypes(['widget_container' => ['string', 'null']]);

        $context->getData()->setDefault(
            'widget_id',
            function () {
                return '$request._wid';
            },
            function () {
                if (!$this->request) {
                    throw new \BadMethodCallException('The request expected.');
                }

                return $this->request->query->get('_wid') ?: $this->request->request->get('_wid');
            }
        );
    }
}
