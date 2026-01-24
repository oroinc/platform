<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers condition services tagged with `oro_action.condition` into the expression extension.
 *
 * This compiler pass collects all services tagged with the condition tag and
 * registers them with the expression extension factory for use in action conditions.
 */
class ConditionPass extends AbstractPass
{
    const EXPRESSION_TAG = 'oro_action.condition';
    const EXTENSION_SERVICE_ID = 'oro_action.expression.extension';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        $this->processTypes($container, self::EXTENSION_SERVICE_ID, self::EXPRESSION_TAG);
    }
}
