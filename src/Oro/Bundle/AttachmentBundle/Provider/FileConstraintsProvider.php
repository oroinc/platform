<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\DependencyInjection\Configuration as AttachmentConfiguration;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypesConverter;
use Oro\Bundle\ConfigBundle\Config\ConfigManager as SystemConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Provides a list of constraints for uploaded file.
 */
class FileConstraintsProvider implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var SystemConfigManager */
    private $systemConfigManager;

    /** @var EntityConfigManager */
    private $entityConfigManager;

    /**
     * @param SystemConfigManager $configManager
     * @param EntityConfigManager $entityConfigManager
     */
    public function __construct(SystemConfigManager $configManager, EntityConfigManager $entityConfigManager)
    {
        $this->systemConfigManager = $configManager;
        $this->entityConfigManager = $entityConfigManager;
        $this->logger = new NullLogger();
    }

    /**
     * @return array
     */
    public function getFileMimeTypes(): array
    {
        return MimeTypesConverter::convertToArray(
            $this->systemConfigManager->get('oro_attachment.upload_file_mime_types', '')
        );
    }

    /**
     * @return array
     */
    public function getImageMimeTypes(): array
    {
        return MimeTypesConverter::convertToArray(
            $this->systemConfigManager->get('oro_attachment.upload_image_mime_types', '')
        );
    }

    /**
     * Gets file and image mime types from system config.
     *
     * @return array
     */
    public function getMimeTypes(): array
    {
        return array_unique(array_merge($this->getFileMimeTypes(), $this->getImageMimeTypes()));
    }

    /**
     * @return array
     */
    public function getMimeTypesAsChoices(): array
    {
        $mimeTypes = $this->getMimeTypes();

        return array_combine($mimeTypes, $mimeTypes);
    }

    /**
     * Gets file and image mime types from entity config.
     *
     * @param string $entityClass
     *
     * @return array
     */
    public function getAllowedMimeTypesForEntity(string $entityClass): array
    {
        $entityConfig = $this->getAttachmentEntityConfig($entityClass);
        if ($entityConfig) {
            $mimeTypes = MimeTypesConverter::convertToArray($entityConfig->get('mimetypes'));
        }

        if (empty($mimeTypes)) {
            $mimeTypes = $this->getMimeTypes();
        }

        return $mimeTypes;
    }

    /**
     * @param string $entityClass
     *
     * @return ConfigInterface|null
     */
    private function getAttachmentEntityConfig(string $entityClass): ?ConfigInterface
    {
        try {
            $attachmentFieldConfig = $this->entityConfigManager->getEntityConfig('attachment', $entityClass);
        } catch (RuntimeException $e) {
            $this->logger
                ->warning(
                    sprintf('Entity config for %s entity class was not found', $entityClass),
                    ['exception' => $e]
                );

            return null;
        }

        return $attachmentFieldConfig;
    }

    /**
     * Gets file and image mime types from entity field config.
     *
     * @param string $entityClass
     * @param string $fieldName
     *
     * @return array
     */
    public function getAllowedMimeTypesForEntityField(string $entityClass, string $fieldName): array
    {
        $entityFieldConfig = $this->getAttachmentFieldConfig($entityClass, $fieldName);
        if ($entityFieldConfig) {
            $mimeTypes = MimeTypesConverter::convertToArray($entityFieldConfig->get('mimetypes'));
        }

        if (empty($mimeTypes)) {
            if ($entityFieldConfig && $entityFieldConfig->getId()->getFieldType() === 'image') {
                $mimeTypes = $this->getImageMimeTypes();
            } else {
                $mimeTypes = $this->getFileMimeTypes();
            }
        }

        return $mimeTypes;
    }

    /**
     * @param string $entityClass
     * @param string $fieldName
     *
     * @return ConfigInterface|null
     */
    private function getAttachmentFieldConfig(string $entityClass, string $fieldName): ?ConfigInterface
    {
        try {
            $attachmentFieldConfig = $this->entityConfigManager->getFieldConfig('attachment', $entityClass, $fieldName);
        } catch (RuntimeException $e) {
            $this->logger
                ->warning(
                    sprintf(
                        'Entity field config for %s entity class and %s field was not found',
                        $entityClass,
                        $fieldName
                    ),
                    ['exception' => $e]
                );

            return null;
        }

        return $attachmentFieldConfig;
    }

    /**
     * Gets max allowed file size from system config.
     *
     * @return int
     */
    public function getMaxSize(): int
    {
        $maxFileSize = $this->systemConfigManager->get('oro_attachment.maxsize');

        return (int) $maxFileSize * AttachmentConfiguration::BYTES_MULTIPLIER;
    }

    /**
     * Gets max allowed file size from entity config.
     *
     * @param string $entityClass
     *
     * @return int
     */
    public function getMaxSizeForEntity(string $entityClass): int
    {
        $entityConfig = $this->getAttachmentEntityConfig($entityClass);
        if ($entityConfig) {
            $maxFileSize = (int)$entityConfig->get('maxsize') * AttachmentConfiguration::BYTES_MULTIPLIER;
        }

        if (empty($maxFileSize)) {
            $maxFileSize = $this->getMaxSize();
        }

        return $maxFileSize;
    }

    /**
     * Gets max allowed file size from entity field config.
     *
     * @param string $entityClass
     * @param string $fieldName
     *
     * @return int
     */
    public function getMaxSizeForEntityField(string $entityClass, string $fieldName): int
    {
        $entityFieldConfig = $this->getAttachmentFieldConfig($entityClass, $fieldName);
        if ($entityFieldConfig) {
            $maxFileSize = (int)$entityFieldConfig->get('maxsize') * AttachmentConfiguration::BYTES_MULTIPLIER;
        }

        if (empty($maxFileSize)) {
            $maxFileSize = $this->getMaxSize();
        }

        return $maxFileSize;
    }
}
