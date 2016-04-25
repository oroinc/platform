<?php

namespace Oro\Bundle\AttachmentBundle\EntityConfig;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper;

/**
 * @deprecated since 1.9. Use {@see Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper} instead
 */
class AttachmentConfig
{
    /** @var AttachmentAssociationHelper */
    protected $attachmentAssociationHelper;

    /**
     * @param AttachmentAssociationHelper $attachmentAssociationHelper
     */
    public function __construct(AttachmentAssociationHelper $attachmentAssociationHelper)
    {
        $this->attachmentAssociationHelper = $attachmentAssociationHelper;
    }

    /**
     * Checks if the entity can has notes
     *
     * @param object $entity
     * @return bool
     * @deprecated since 1.9. Use {@see Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper} instead
     */
    public function isAttachmentAssociationEnabled($entity)
    {
        if (!is_object($entity)) {
            return false;
        }

        return $this->attachmentAssociationHelper->isAttachmentAssociationEnabled(ClassUtils::getClass($entity));
    }
}
