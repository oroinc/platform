<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Step;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;

/**
 * Merge handler step for removing result data
 */
class RemoveEntitiesStep implements DependentMergeStepInterface
{
    public function __construct(private EntityManager $entityManager, private DoctrineHelper $doctrineHelper)
    {
    }

    /**
     * Merge fields
     */
    #[\Override]
    public function run(EntityData $data)
    {
        $masterEntity = $data->getMasterEntity();

        foreach ($data->getEntities() as $entity) {
            if (!$this->doctrineHelper->isEntityEqual($masterEntity, $entity)) {
                $this->entityManager->remove($entity);
            }
        }
    }

    #[\Override]
    public function getDependentSteps()
    {
        return array('Oro\\Bundle\\EntityMergeBundle\\Model\\Step\\MergeFieldsStep');
    }
}
