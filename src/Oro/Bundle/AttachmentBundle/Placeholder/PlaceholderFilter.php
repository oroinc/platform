<?php

namespace Oro\Bundle\AttachmentBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class PlaceholderFilter
{
    /** @var AttachmentAssociationHelper */
    protected $attachmentAssociationHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param AttachmentAssociationHelper $attachmentAssociationHelper
     * @param DoctrineHelper              $doctrineHelper
     */
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
        if (!is_object($entity)
            || !$this->doctrineHelper->isManageableEntity($entity)
            || $this->doctrineHelper->isNewEntity($entity)
        ) {
            return false;
        }

        return $this->attachmentAssociationHelper->isAttachmentAssociationEnabled(ClassUtils::getClass($entity));
    }
}
