<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Step;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;

class RemoveEntitiesStep implements DependentMergeStepInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param EntityManager $entityManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(EntityManager $entityManager, DoctrineHelper $doctrineHelper)
    {
        $this->entityManager = $entityManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Merge fields
     *
     * @param EntityData $data
     */
    public function run(EntityData $data)
    {
        $masterEntity = $data->getMasterEntity();

        foreach ($data->getEntities() as $entity) {
            if (!$this->doctrineHelper->isEntityEqual($masterEntity, $entity)) {
                $this->entityManager->remove($entity);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDependentSteps()
    {
        return array('Oro\\Bundle\\EntityMergeBundle\\Model\\Step\\MergeFieldsStep');
    }
}
