<?php

namespace Oro\Bundle\AttachmentBundle\Placeholder;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentConfig;

class PlaceholderFilter
{
    /**
     * @var AttachmentConfig
     */
    protected $config;

    public function __construct(AttachmentConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Delegated method
     *
     * @param object $entity
     * @return bool
     */
    public function isAttachmentAssociationEnabled($entity)
    {
        return $this->config->isAttachmentAssociationEnabled($entity);
    }
}
