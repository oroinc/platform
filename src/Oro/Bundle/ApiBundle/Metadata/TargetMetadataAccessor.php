<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Config\TargetConfigExtraBuilder;
use Oro\Bundle\ApiBundle\Metadata\Extra\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * The accessor to target metadata by a specified target class name and association path.
 * It is used for multi-target associations.
 * @see \Oro\Bundle\ApiBundle\Model\EntityIdentifier
 * @see \Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\ExpandMultiTargetAssociations
 * @see \Oro\Bundle\ApiBundle\Processor\Context::initializeMetadata
 */
class TargetMetadataAccessor implements TargetMetadataAccessorInterface
{
    private string $version;
    private RequestType $requestType;
    private MetadataProvider $metadataProvider;
    /** @var MetadataExtraInterface[] */
    private array $metadataExtras;
    private ConfigProvider $configProvider;
    /** @var ConfigExtraInterface[] */
    private array $configExtras;
    /** @var array [association path => ConfigExtraInterface[], ...] */
    private array $processedConfigExtras = [];

    /**
     * @param string                   $version
     * @param RequestType              $requestType
     * @param MetadataProvider         $metadataProvider
     * @param MetadataExtraInterface[] $metadataExtras
     * @param ConfigProvider           $configProvider
     * @param ConfigExtraInterface[]   $configExtras
     */
    public function __construct(
        string $version,
        RequestType $requestType,
        MetadataProvider $metadataProvider,
        array $metadataExtras,
        ConfigProvider $configProvider,
        array $configExtras
    ) {
        $this->version = $version;
        $this->requestType = $requestType;
        $this->metadataProvider = $metadataProvider;
        $this->metadataExtras = $metadataExtras;
        $this->configProvider = $configProvider;
        $this->configExtras = $configExtras;
    }

    #[\Override]
    public function getTargetMetadata(string $targetClassName, ?string $associationPath): ?EntityMetadata
    {
        $config = $this->configProvider->getConfig(
            $targetClassName,
            $this->version,
            $this->requestType,
            $this->buildConfigExtras($associationPath, !$this->isExpandRequested($associationPath))
        );
        if (!$config->hasDefinition()) {
            return null;
        }

        return $this->metadataProvider->getMetadata(
            $targetClassName,
            $this->version,
            $this->requestType,
            $config->getDefinition(),
            $this->metadataExtras
        );
    }

    private function isExpandRequested(?string $associationPath): bool
    {
        if (!$associationPath) {
            return true;
        }

        /** @var ExpandRelatedEntitiesConfigExtra|null $expandConfigExtra */
        $expandConfigExtra = $this->getConfigExtra(ExpandRelatedEntitiesConfigExtra::NAME);
        if (null === $expandConfigExtra) {
            return false;
        }

        return $expandConfigExtra->isExpandRequested($associationPath);
    }

    private function getConfigExtra(string $extraName): ?ConfigExtraInterface
    {
        foreach ($this->configExtras as $extra) {
            if ($extra->getName() === $extraName) {
                return $extra;
            }
        }

        return null;
    }

    /**
     * @return ConfigExtraInterface[]
     */
    private function buildConfigExtras(?string $associationPath, bool $idOnly): array
    {
        $cacheKey = ($associationPath ?? '') . ($idOnly ? '|idOnly' : '');
        if (!isset($this->processedConfigExtras[$cacheKey])) {
            $configExtras = TargetConfigExtraBuilder::buildConfigExtras($this->configExtras, $associationPath);
            if ($idOnly && !$this->hasFilterIdentifierFieldsConfigExtra($configExtras)) {
                $configExtras[] = new FilterIdentifierFieldsConfigExtra();
            }
            $this->processedConfigExtras[$cacheKey] = $configExtras;
        }

        return $this->processedConfigExtras[$cacheKey];
    }

    private function hasFilterIdentifierFieldsConfigExtra(array $configExtras): bool
    {
        foreach ($configExtras as $extra) {
            if ($extra instanceof FilterIdentifierFieldsConfigExtra) {
                return true;
            }
        }

        return false;
    }
}
