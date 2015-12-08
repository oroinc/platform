<?php

namespace Oro\Bundle\NoteBundle\Model\Strategy;

use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\StrategyInterface;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\NoteBundle\Model\MergeModes;

/**
 * Class UniteStrategy
 * @package Oro\Bundle\NoteBundle\Model\Strategy
 */
class UniteStrategy implements StrategyInterface
{
    /** @var DoctrineHelper  */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(FieldData $fieldData)
    {
        $entityData    = $fieldData->getEntityData();
        $masterEntity  = $entityData->getMasterEntity();

        $entities = $fieldData->getEntityData()->getEntities();
        $entitiesIds = [];
        foreach ($entities as $sourceEntity) {
            if ($sourceEntity->getId() !== $masterEntity->getId()) {
                $entitiesIds[] = $sourceEntity->getId();
            }
        }

        $queryBuilder = $this->doctrineHelper->getEntityRepository('OroNoteBundle:Note')
            ->getBaseAssociatedNotesQB(ClassUtils::getRealClass($masterEntity), $entitiesIds);
        $notes = $queryBuilder->getQuery()->getResult();

        foreach ($notes as $note) {
            $note->setTarget($masterEntity);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(FieldData $fieldData)
    {
        return $fieldData->getMode() === MergeModes::NOTES_UNITE;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'notes_unite';
    }
}
