<?php

namespace Oro\Bundle\AttachmentBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Filters entities to determine if attachment associations are enabled.
 *
 * This filter checks whether a given entity is eligible for attachment associations
 * by verifying that it is a valid, manageable Doctrine entity that has been persisted.
 * It leverages the {@see AttachmentAssociationHelper} to determine if the entity's class has
 * attachment associations enabled in the system configuration. This is commonly used
 * in placeholder rendering to conditionally display attachment-related UI elements.
 */
class PlaceholderFilter
{
    /** @var AttachmentAssociationHelper */
    protected $attachmentAssociationHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    public function __construct(
        AttachmentAssociationHelper $attachmentAssociationHelper,
        DoctrineHelper $doctrineHelper
    ) {
        $this->attachmentAssociationHelper = $attachmentAssociationHelper;
        $this->doctrineHelper              = $doctrineHelper;
    }

    /**
     * @param object $entity
     *
     * @return bool
     */
    public function isAttachmentAssociationEnabled($entity)
    {
        if (
            !is_object($entity)
            || !$this->doctrineHelper->isManageableEntity($entity)
            || $this->doctrineHelper->isNewEntity($entity)
        ) {
            return false;
        }

        return $this->attachmentAssociationHelper->isAttachmentAssociationEnabled(ClassUtils::getClass($entity));
    }
}
