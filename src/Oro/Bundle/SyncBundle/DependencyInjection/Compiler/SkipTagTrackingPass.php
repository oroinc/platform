<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class SkipTagTrackingPass implements CompilerPassInterface
{
    const SERVICE_ID = 'oro_sync.event_listener.doctrine_tag';

    /** @var array */
    protected $skippedEntityClasses = [
        'Oro\Bundle\DataAuditBundle\Entity\Audit',
        'Oro\Bundle\DataAuditBundle\Entity\AuditData',
        'Oro\Bundle\NavigationBundle\Entity\PageState',
        'Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem',
        'Oro\Bundle\SearchBundle\Entity\Item',
        'Oro\Bundle\SearchBundle\Entity\IndexText',
        'Oro\Bundle\SearchBundle\Entity\IndexInteger',
        'Oro\Bundle\SearchBundle\Entity\IndexDecimal',
        'Oro\Bundle\SearchBundle\Entity\IndexDatetime',
        'Akeneo\Bundle\BatchBundle\Entity\JobExecution',
        'Akeneo\Bundle\BatchBundle\Entity\StepExecution',
        'JMS\JobQueueBundle\Entity\Job'
    ];

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::SERVICE_ID)) {
            $definition = $container->getDefinition(self::SERVICE_ID);

            foreach ($this->getSkippedEntityClasses() as $entityClass) {
                $definition->addMethodCall('markSkipped', [$entityClass]);
            }
        }
    }

    /**
     * @return array
     */
    protected function getSkippedEntityClasses()
    {
        foreach ($this->skippedEntityClasses as $key => $entityClass) {
            if (!class_exists($entityClass)) {
                unset($this->skippedEntityClasses[$key]);
            }
        }

        return $this->skippedEntityClasses;
    }
}
