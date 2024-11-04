<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Psr\Log\LoggerInterface;

/**
 * Provides attachment entity config.
 */
class AttachmentEntityConfigProvider implements AttachmentEntityConfigProviderInterface
{
    private EntityConfigManager $entityConfigManager;
    private LoggerInterface $logger;

    public function __construct(EntityConfigManager $entityConfigManager, LoggerInterface $logger)
    {
        $this->entityConfigManager = $entityConfigManager;
        $this->logger = $logger;
    }

    #[\Override]
    public function getFieldConfig(string $entityClass, string $fieldName): ?ConfigInterface
    {
        if (!$this->entityConfigManager->hasConfig($entityClass, $fieldName)) {
            $this->logger->warning(
                'Attachment entity field config for {entityClass} entity class and {fieldName} field was not found.',
                ['entityClass' => $entityClass, 'fieldName' => $fieldName]
            );

            // Either entity or field is not configurable.
            return null;
        }

        return $this->entityConfigManager
            ->getFieldConfig('attachment', $entityClass, $fieldName);
    }

    #[\Override]
    public function getEntityConfig(string $entityClass): ?ConfigInterface
    {
        if (!$this->entityConfigManager->hasConfig($entityClass)) {
            $this->logger->warning(
                'Attachment entity config for {entityClass} entity class was not found.',
                ['entityClass' => $entityClass]
            );

            // Entity is not configurable.
            return null;
        }

        return $this->entityConfigManager->getEntityConfig('attachment', $entityClass);
    }
}
