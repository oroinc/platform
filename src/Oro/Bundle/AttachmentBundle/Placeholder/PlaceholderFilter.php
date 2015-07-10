<?php

namespace Oro\Bundle\AttachmentBundle\Placeholder;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentConfig;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class PlaceholderFilter
{
    /** @var AttachmentConfig */
    protected $config;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    public function __construct(AttachmentConfig $config, DoctrineHelper $doctrineHelper)
    {
        $this->config = $config;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Delegated method
     *
     * @param object $entity
     * @return bool
     */
    public function isAttachmentAssociationEnabled($entity)
    {
        if (!is_object($entity) || $this->doctrineHelper->isNewEntity($entity) === true) {
            return false;
        }

        return $this->config->isAttachmentAssociationEnabled($entity);
    }
}
