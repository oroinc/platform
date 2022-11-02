<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Provides attachment entity config.
 */
class AttachmentEntityConfigProvider implements AttachmentEntityConfigProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var EntityConfigManager */
    private $entityConfigManager;

    public function __construct(EntityConfigManager $entityConfigManager)
    {
        $this->entityConfigManager = $entityConfigManager;
        $this->setLogger(new NullLogger());
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldConfig(string $entityClass, string $fieldName): ?ConfigInterface
    {
        if (!$this->entityConfigManager->hasConfig($entityClass, $fieldName)) {
            $this->logger
                ->warning(
                    sprintf(
                        'Attachment entity field config for %s entity class and %s field was not found',
                        $entityClass,
                        $fieldName
                    )
                );

            // Either entity or field is not configurable.
            return null;
        }

        return $this->entityConfigManager
            ->getFieldConfig('attachment', $entityClass, $fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfig(string $entityClass): ?ConfigInterface
    {
        if (!$this->entityConfigManager->hasConfig($entityClass)) {
            $this->logger
                ->warning(
                    sprintf('Attachment entity config for %s entity class was not found', $entityClass)
                );

            // Entity is not configurable.
            return null;
        }

        return $this->entityConfigManager->getEntityConfig('attachment', $entityClass);
    }
}
