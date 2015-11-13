<?php

namespace Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

use Oro\Bundle\IntegrationBundle\Exception\LogicException;

class BlockingJobsPass implements CompilerPassInterface
{
    const BLOCKED_JOB_TAG_NAME   = 'oro_integration.blocking_job';
    const MANAGER_ID = 'oro_integration.manager.blocking_job';
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $manager = $container->getDefinition(self::MANAGER_ID);
        $this->processBlockingJobs($manager, $container);
    }

    /**
     * Pass integration types to a manager
     *
     * @param Definition       $managerDefinition
     * @param ContainerBuilder $container
     *
     * @throws LogicException
     */
    protected function processBlockingJobs(Definition $managerDefinition, ContainerBuilder $container)
    {
        $integrations = $container->findTaggedServiceIds(self::BLOCKED_JOB_TAG_NAME);

        foreach ($integrations as $serviceId => $tags) {
            $ref = new Reference($serviceId);
            foreach ($tags as $tagAttrs) {
                if (!isset($tagAttrs['type'])) {
                    throw new LogicException(sprintf('Could not retrieve type attribute for "%s"', $serviceId));
                }

                $managerDefinition->addMethodCall('addJob', [$tagAttrs['type'], $ref]);
            }
        }
    }
}
