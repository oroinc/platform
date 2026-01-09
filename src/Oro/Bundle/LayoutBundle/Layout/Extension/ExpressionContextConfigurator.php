<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Configures layout context options for expression evaluation.
 *
 * This configurator sets up context options that control expression evaluation behavior,
 * including whether expressions should be evaluated immediately or deferred, and the
 * character encoding to use for expression processing.
 */
class ExpressionContextConfigurator implements ContextConfiguratorInterface
{
    #[\Override]
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
