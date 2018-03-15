<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

class ExpressionContextConfigurator implements ContextConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setDefaults(
                [
                    'expressions_evaluate' => true,
                    'expressions_evaluate_deferred' => true
                ]
            )
            ->setDefined(['expressions_encoding'])
            ->setAllowedTypes('expressions_evaluate', 'bool')
            ->setAllowedTypes('expressions_evaluate_deferred', 'bool')
            ->setAllowedTypes('expressions_encoding', ['string', 'null']);
    }
}
