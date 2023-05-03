<?php

namespace Oro\Bundle\AttachmentBundle\Api;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides information about associations of the attachment entity with other entities.
 */
class AttachmentAssociationProvider implements ResetInterface
{
    private const KEY_DELIMITER = '|';

    private DoctrineHelper $doctrineHelper;
    private AttachmentAssociationHelper $attachmentAssociationHelper;
    private array $attachmentAssociationNames = [];

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AttachmentAssociationHelper $attachmentAssociationHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->attachmentAssociationHelper = $attachmentAssociationHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->attachmentAssociationNames = [];
    }

    public function getAttachmentAssociationName(
        string $entityClass,
        string $version,
        RequestType $requestType
    ): ?string {
        $cacheKey = (string)$requestType . self::KEY_DELIMITER . $version . self::KEY_DELIMITER . $entityClass;
        if (\array_key_exists($cacheKey, $this->attachmentAssociationNames)) {
            return $this->attachmentAssociationNames[$cacheKey];
        }

        $result = null;
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)
            && $this->attachmentAssociationHelper->isAttachmentAssociationEnabled($entityClass)
        ) {
            $result = ExtendHelper::buildAssociationName($entityClass);
        }
        $this->attachmentAssociationNames[$cacheKey] = $result;

        return $result;
    }
}
