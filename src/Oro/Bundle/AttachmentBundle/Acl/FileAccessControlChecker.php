<?php

namespace Oro\Bundle\AttachmentBundle\Acl;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProviderInterface;

/**
 * Checks if File should be checked with ACL.
 */
class FileAccessControlChecker
{
    private AttachmentEntityConfigProviderInterface $attachmentEntityConfigProvider;

    public function __construct(AttachmentEntityConfigProviderInterface $configManager)
    {
        $this->attachmentEntityConfigProvider = $configManager;
    }

    public function isCoveredByAcl(File $file): bool
    {
        if ($file->getExternalUrl()) {
            // We cannot protect file that is stored externally.
            return false;
        }

        $parentEntityClass = $file->getParentEntityClass();
        $parentEntityFieldName = $file->getParentEntityFieldName();
        $parentEntityId = $file->getParentEntityId();
        if (!$parentEntityClass || !$parentEntityId || !$parentEntityFieldName) {
            // We cannot check file with ACL without class name, id or field name.
            return false;
        }

        $config = $this->attachmentEntityConfigProvider->getFieldConfig($parentEntityClass, $parentEntityFieldName);
        if (!$config) {
            return false;
        }

        return (bool) $config->get('acl_protected', false, true);
    }
}
