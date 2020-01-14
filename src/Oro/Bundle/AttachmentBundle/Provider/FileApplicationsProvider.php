<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

/**
 * Resolves allowed applications from the given File entity.
 */
class FileApplicationsProvider
{
    private const DEFAULT_ALLOWED_APPLICATIONS = [CurrentApplicationProviderInterface::DEFAULT_APPLICATION];

    /** @var AttachmentEntityConfigProviderInterface */
    private $attachmentEntityConfigProvider;

    /**
     * @param AttachmentEntityConfigProviderInterface $attachmentEntityConfigProvider
     */
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

        $config = $this->attachmentEntityConfigProvider->getFieldConfig($parentEntityClass, $parentEntityFieldName);
        if (!$config) {
            return self::DEFAULT_ALLOWED_APPLICATIONS;
        }

        return $this->getAllowedApplications($config);
    }

    /**
     * @param ConfigInterface $config
     *
     * @return array
     */
    private function getAllowedApplications(ConfigInterface $config): array
    {
        return (array)$config->get('file_applications', false, self::DEFAULT_ALLOWED_APPLICATIONS);
    }
}
