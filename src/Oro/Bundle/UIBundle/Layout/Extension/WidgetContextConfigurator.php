<?php

namespace Oro\Bundle\UIBundle\Layout\Extension;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;

use Symfony\Component\HttpFoundation\Request;

class WidgetContextConfigurator implements ContextConfiguratorInterface
{
    const PARAM_WIDGET = 'widget';

    /** @var Request */
    protected $request;

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getDataResolver()
            ->setOptional([self::PARAM_WIDGET])
            ->setAllowedTypes([self::PARAM_WIDGET => ['string', 'null']])
            ->setNormalizers(
                [
                    self::PARAM_WIDGET => function ($options, $widget) {
                        if (null === $widget && $this->request) {
                            $widget = $this->request->query
                                ->get('_widgetContainer', $this->request->request->get('_widgetContainer'));
                        }

                        return $widget;
                    }
                ]
            );
    }

    /**
     * Synchronized DI method call, sets current request for further usage
     *
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }
}
