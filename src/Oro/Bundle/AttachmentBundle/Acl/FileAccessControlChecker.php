<?php

namespace Oro\Bundle\AttachmentBundle\Acl;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Checks if File should be checked with ACL.
 */
class FileAccessControlChecker
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
     * {@inheritdoc}
     */
    public function isCoveredByAcl(File $file): bool
    {
        $parentEntityClass = $file->getParentEntityClass();
        $parentEntityFieldName = $file->getParentEntityFieldName();
        $parentEntityId = $file->getParentEntityId();
        if (!$parentEntityClass || !$parentEntityId || !$parentEntityFieldName) {
            // We cannot check file with ACL without class name, id or field name.
            return false;
        }

        $config = $this->configManager
            ->getFieldConfig('attachment', $parentEntityClass, $parentEntityFieldName);


        return (bool) $config->get('acl_protected', false, true);
    }
}
