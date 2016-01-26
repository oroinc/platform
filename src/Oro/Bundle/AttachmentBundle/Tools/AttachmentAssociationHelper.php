<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class AttachmentAssociationHelper
{
    const ATTACHMENT_ENTITY = 'Oro\Bundle\AttachmentBundle\Entity\Attachment';

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Checks whether attachments are enabled for a given entity type.
     *
     * @param string $entityClass The target entity class
     * @param bool $accessible    Whether an association with the target entity should be checked
     *                            to be ready to use in a business logic.
     *                            It means that the association should exist and should not be marked as deleted.
     *
     * @return bool
     */
    public function isAttachmentAssociationEnabled($entityClass, $accessible = true)
    {
        if (!$this->configManager->hasConfig($entityClass)) {
            return false;
        }
        if (!$this->configManager->getEntityConfig('attachment', $entityClass)->is('enabled')) {
            return false;
        }

        return
            !$accessible
            || $this->isAttachmentAssociationAccessible($entityClass);
    }

    /**
     * Check if an association between a given entity type and attachments is ready to be used in a business logic.
     * It means that the association should exist and should not be marked as deleted.
     *
     * @param string $entityClass The target entity class
     *
     * @return bool
     */
    protected function isAttachmentAssociationAccessible($entityClass)
    {
        $associationName = ExtendHelper::buildAssociationName($entityClass);
        if (!$this->configManager->hasConfig(self::ATTACHMENT_ENTITY, $associationName)) {
            return false;
        }

        return ExtendHelper::isFieldAccessible(
            $this->configManager->getFieldConfig('extend', self::ATTACHMENT_ENTITY, $associationName)
        );
    }
}
