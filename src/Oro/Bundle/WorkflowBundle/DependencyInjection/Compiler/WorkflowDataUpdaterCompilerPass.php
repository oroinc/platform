<?php

namespace Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class WorkflowDataUpdaterCompilerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    const TAG = 'oro_workflow.data_updater';
    const SERVICE_ID = 'oro_workflow.data_updater.chain';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices($container, self::SERVICE_ID, self::TAG, 'addUpdater');
    }
}
