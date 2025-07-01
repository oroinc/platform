<?php

namespace Oro\Bundle\AttachmentBundle\Api;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\AttachmentBundle\Helper\FieldConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides information about multi files and multi images associations.
 */
class MultiFileAssociationProvider implements ResetInterface
{
    private const KEY_DELIMITER = '|';

    private array $multiFileAssociationNames = [];

    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly ConfigManager $configManager
    ) {
    }

    #[\Override]
    public function reset(): void
    {
        $this->multiFileAssociationNames = [];
    }

    public function getMultiFileAssociationNames(
        string $entityClass,
        string $version,
        RequestType $requestType
    ): array {
        $cacheKey = (string)$requestType . self::KEY_DELIMITER . $version . self::KEY_DELIMITER . $entityClass;
        if (\array_key_exists($cacheKey, $this->multiFileAssociationNames)) {
            return $this->multiFileAssociationNames[$cacheKey];
        }

        $multiFileAssociationNames = [];
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)
            && $this->configManager->hasConfig($entityClass)
        ) {
            $fieldConfigs = $this->configManager->getConfigs('extend', $entityClass);
            foreach ($fieldConfigs as $fieldConfig) {
                if (FieldConfigHelper::isMultiField($fieldConfig->getId())
                    && ExtendHelper::isFieldAccessible($fieldConfig)
                ) {
                    $multiFileAssociationNames[] = $fieldConfig->getId()->getFieldName();
                }
            }
        }
        $this->multiFileAssociationNames[$cacheKey] = $multiFileAssociationNames;

        return $multiFileAssociationNames;
    }
}
