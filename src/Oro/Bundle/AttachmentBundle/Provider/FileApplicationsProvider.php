<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

/**
 * Resolves allowed applications for the given File entity or field name
 */
class FileApplicationsProvider
{
    private const DEFAULT_ALLOWED_APPLICATIONS = [CurrentApplicationProviderInterface::DEFAULT_APPLICATION];

    /** @var AttachmentEntityConfigProviderInterface */
    private $attachmentEntityConfigProvider;

    public function __construct(AttachmentEntityConfigProviderInterface $attachmentEntityConfigProvider)
    {
        $this->attachmentEntityConfigProvider = $attachmentEntityConfigProvider;
    }

    /**
     * @param File $file
     *
     * @return array Can contain "default", "commerce" or both these values.
     */
    public function getFileApplications(File $file): array
    {
        $parentEntityClass = $file->getParentEntityClass();
        $parentEntityId = $file->getParentEntityId();
        $parentEntityFieldName = $file->getParentEntityFieldName();
        if (!$parentEntityClass || !$parentEntityFieldName || !$parentEntityId) {
            return [CurrentApplicationProviderInterface::DEFAULT_APPLICATION];
        }

        return $this->getFileApplicationsForField($parentEntityClass, $parentEntityFieldName);
    }

    public function getFileApplicationsForField(string $className, string $fieldName): array
    {
        $config = $this->attachmentEntityConfigProvider->getFieldConfig($className, $fieldName);
        if (!$config) {
            return self::DEFAULT_ALLOWED_APPLICATIONS;
        }

        return $this->getAllowedApplications($config);
    }

    private function getAllowedApplications(ConfigInterface $config): array
    {
        return (array)$config->get('file_applications', false, self::DEFAULT_ALLOWED_APPLICATIONS);
    }
}
