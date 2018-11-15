<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Loads a list of resources that do not have an identifier.
 */
class ResourcesWithoutIdentifierLoader
{
    /** @var ConfigProvider */
    private $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @param string        $version
     * @param RequestType   $requestType
     * @param ApiResource[] $resources
     *
     * @return string[] The list of class names
     */
    public function load(string $version, RequestType $requestType, array $resources): array
    {
        $resourcesWithoutIdentifier = [];
        foreach ($resources as $resource) {
            $entityClass = $resource->getEntityClass();
            if (!$this->hasIdentifierFields($entityClass, $version, $requestType)) {
                $resourcesWithoutIdentifier[] = $entityClass;
            }
        }

        return $resourcesWithoutIdentifier;
    }

    /**
     * @param string      $entityClass
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return bool
     */
    private function hasIdentifierFields(string $entityClass, string $version, RequestType $requestType): bool
    {
        $config = $this->configProvider
            ->getConfig(
                $entityClass,
                $version,
                $requestType,
                [new EntityDefinitionConfigExtra(ApiActions::GET), new FilterIdentifierFieldsConfigExtra()]
            )
            ->getDefinition();

        $result = false;
        if (null !== $config) {
            $idFieldNames = $config->getIdentifierFieldNames();
            if (!empty($idFieldNames)) {
                $result = true;
            }
        }

        return $result;
    }
}
