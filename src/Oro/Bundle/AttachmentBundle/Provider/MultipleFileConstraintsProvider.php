<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

/**
 * Provides a list of constraints for multiple file collection field.
 */
class MultipleFileConstraintsProvider
{
    /** @var AttachmentEntityConfigProviderInterface */
    private $attachmentEntityConfigProvider;

    public function __construct(AttachmentEntityConfigProviderInterface $attachmentEntityConfigProvider)
    {
        $this->attachmentEntityConfigProvider = $attachmentEntityConfigProvider;
    }

    /**
     * Gets allowed max number of files.
     */
    public function getMaxNumberOfFiles(): int
    {
        return 0;
    }

    /**
     * Gets allowed max number of files from entity config.
     */
    public function getMaxNumberOfFilesForEntity(string $entityClass): int
    {
        $entityConfig = $this->attachmentEntityConfigProvider->getEntityConfig($entityClass);
        if ($entityConfig) {
            $value = (int)$entityConfig->get('max_number_of_files');
        }

        if (empty($value)) {
            $value = $this->getMaxNumberOfFiles();
        }

        return (int)$value;
    }

    /**
     * Gets allowed max number of files from entity field config.
     */
    public function getMaxNumberOfFilesForEntityField(string $entityClass, string $fieldName): int
    {
        $entityFieldConfig = $this->attachmentEntityConfigProvider->getFieldConfig($entityClass, $fieldName);
        if ($entityFieldConfig) {
            $value = (int)$entityFieldConfig->get('max_number_of_files');
        }

        if (empty($value)) {
            $value = $this->getMaxNumberOfFiles();
        }

        return (int)$value;
    }
}
