<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConditionPass extends AbstractPass
{
    const EXPRESSION_TAG = 'oro_action.condition';
    const EXTENSION_SERVICE_ID = 'oro_action.expression.extension';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->processTypes($container, self::EXTENSION_SERVICE_ID, self::EXPRESSION_TAG);
    }
}
