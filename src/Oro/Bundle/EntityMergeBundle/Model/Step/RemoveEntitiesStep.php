<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Step;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;

/**
 * Merge handler step for removing result data
 */
class RemoveEntitiesStep implements DependentMergeStepInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function run(EntityData $data): void
    {
        $entityManager = $this->doctrine->getManager();
        $masterEntity = $data->getMasterEntity();
        foreach ($data->getEntities() as $entity) {
            if (!$this->doctrineHelper->isEntityEqual($masterEntity, $entity)) {
                $entityManager->remove($entity);
            }
        }
    }

    #[\Override]
    public function getDependentSteps(): array
    {
        return [MergeFieldsStep::class];
    }
}
