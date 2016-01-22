<?php

namespace Oro\Bundle\NoteBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NoteBundle\Tools\NoteAssociationHelper;

class PlaceholderFilter
{
    /** @var NoteAssociationHelper */
    protected $noteAssociationHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param NoteAssociationHelper $noteAssociationHelper
     * @param DoctrineHelper        $doctrineHelper
     */
    public function __construct(
        NoteAssociationHelper $noteAssociationHelper,
        DoctrineHelper $doctrineHelper
    ) {
        $this->noteAssociationHelper = $noteAssociationHelper;
        $this->doctrineHelper        = $doctrineHelper;
    }

    /**
     * Checks if the entity can has notes
     *
     * @param object $entity
     * @return bool
     */
    public function isNoteAssociationEnabled($entity)
    {
        if (!is_object($entity)
            || !$this->doctrineHelper->isManageableEntity($entity)
            || $this->doctrineHelper->isNewEntity($entity)
        ) {
            return false;
        }

        return $this->noteAssociationHelper->isNoteAssociationEnabled(ClassUtils::getClass($entity));
    }
}
