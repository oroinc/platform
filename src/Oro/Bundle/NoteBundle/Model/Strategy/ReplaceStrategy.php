<?php

namespace Oro\Bundle\NoteBundle\Model\Strategy;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\StrategyInterface;
use Oro\Bundle\NoteBundle\Model\MergeModes;
use Symfony\Component\Security\Core\Util\ClassUtils;

/**
 * Class ReplaceStrategy
 * @package Oro\Bundle\NoteBundle\Model\Strategy
 */
class ReplaceStrategy implements StrategyInterface
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
        $sourceEntity  = $fieldData->getSourceEntity();

        $queryBuilder = $this->doctrineHelper->getEntityRepository('OroNoteBundle:Note')
            ->getBaseAssociatedNotesQB(ClassUtils::getRealClass($masterEntity), $masterEntity->getId());
        $notes = $queryBuilder->getQuery()->getResult();

        if (!empty($notes)) {
            $entityManager = $this->doctrineHelper->getEntityManager(current($notes));
            foreach ($notes as $note) {
                $entityManager->remove($note);
            }
        }

        $queryBuilder = $this->doctrineHelper->getEntityRepository('OroNoteBundle:Note')
            ->getBaseAssociatedNotesQB(ClassUtils::getRealClass($masterEntity), $sourceEntity->getId());
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
        return $fieldData->getMode() === MergeModes::NOTES_REPLACE;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'notes_replace';
    }
}
