<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provides a method to check whether attachments are enabled for a specific entity type.
 */
class AttachmentAssociationHelper
{
    private ConfigManager $configManager;

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
    public function isAttachmentAssociationEnabled(string $entityClass, bool $accessible = true): bool
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
     */
    private function isAttachmentAssociationAccessible(string $entityClass): bool
    {
        $associationName = ExtendHelper::buildAssociationName($entityClass);
        if (!$this->configManager->hasConfig(Attachment::class, $associationName)) {
            return false;
        }

        return ExtendHelper::isFieldAccessible(
            $this->configManager->getFieldConfig('extend', Attachment::class, $associationName)
        );
    }
}
