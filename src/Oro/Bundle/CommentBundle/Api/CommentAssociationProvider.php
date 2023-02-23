<?php

namespace Oro\Bundle\CommentBundle\Api;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides information about associations of the comment entity with other entities.
 */
class CommentAssociationProvider implements ResetInterface
{
    private const KEY_DELIMITER = '|';

    private DoctrineHelper $doctrineHelper;
    private ConfigManager $configManager;
    private array $commentAssociationNames = [];

    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->commentAssociationNames = [];
    }

    public function getCommentAssociationName(
        string $entityClass,
        string $version,
        RequestType $requestType
    ): ?string {
        $cacheKey = (string)$requestType . self::KEY_DELIMITER . $version . self::KEY_DELIMITER . $entityClass;
        if (\array_key_exists($cacheKey, $this->commentAssociationNames)) {
            return $this->commentAssociationNames[$cacheKey];
        }

        $result = null;
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)
            && $this->configManager->hasConfig($entityClass)
        ) {
            $entityConfig = $this->configManager->getEntityConfig('comment', $entityClass);
            if ($entityConfig->is('enabled')) {
                $result = ExtendHelper::buildAssociationName($entityClass);
            }
        }
        $this->commentAssociationNames[$cacheKey] = $result;

        return $result;
    }
}
