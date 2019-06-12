<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Resolves allowed applications from the given File entity.
 */
class FileApplicationsProvider
{
    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
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

        $config = $this->configManager->getFieldConfig('attachment', $parentEntityClass, $parentEntityFieldName);

        return $this->getAllowedApplications($config);
    }

    /**
     * @param ConfigInterface $config
     *
     * @return array
     */
    private function getAllowedApplications(ConfigInterface $config): array
    {
        return (array) $config
            ->get('file_applications', false, [CurrentApplicationProviderInterface::DEFAULT_APPLICATION]);
    }
}
