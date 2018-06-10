<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\ActionMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\Version;

/**
 * The base class for different kind of REST API functional tests.
 */
abstract class RestApiTestCase extends ApiTestCase
{
    /**
     * @return array
     */
    protected function getWsseAuthHeader()
    {
        return self::generateWsseAuthHeader(static::USER_NAME, static::USER_PASSWORD);
    }

    /**
     * @return string
     */
    protected function getItemRouteName()
    {
        return 'oro_rest_api_item';
    }

    /**
     * @return string
     */
    protected function getListRouteName()
    {
        return 'oro_rest_api_list';
    }

    /**
     * @return string
     */
    protected function getSubresourceRouteName()
    {
        return 'oro_rest_api_subresource';
    }

    /**
     * @return string
     */
    protected function getRelationshipRouteName()
    {
        return 'oro_rest_api_relationship';
    }

    /**
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return string|null
     */
    protected function getRestApiEntityId(string $entityClass, $entityId)
    {
        if (null === $entityId) {
            return null;
        }

        $metadata = $this->getApiMetadata($entityClass, null, true);
        if (null === $metadata) {
            return $entityId;
        }

        return self::getContainer()->get('oro_api.entity_id_transformer_registry')
            ->getEntityIdTransformer($this->getRequestType())
            ->transform($entityId, $metadata);
    }

    /**
     * @param string      $entityClass
     * @param string|null $action
     *
     * @return EntityDefinitionConfig|null
     */
    protected function getApiConfig(
        string $entityClass,
        string $action = null,
        bool $idOnly = false
    ): ?EntityDefinitionConfig {
        $configExtras = [new EntityDefinitionConfigExtra($action)];
        if ($idOnly) {
            $configExtras[] = new FilterIdentifierFieldsConfigExtra();
        }
        $config = self::getContainer()->get('oro_api.config_provider')->getConfig(
            $entityClass,
            Version::LATEST,
            $this->getRequestType(),
            $configExtras
        );
        if (null === $config) {
            return null;
        }

        return $config->getDefinition();
    }

    /**
     * @param string      $entityClass
     * @param string|null $action
     *
     * @return EntityMetadata|null
     */
    protected function getApiMetadata(
        string $entityClass,
        string $action = null,
        bool $idOnly = false
    ): ?EntityMetadata {
        $config = $this->getApiConfig($entityClass, $action, $idOnly);
        if (null === $config) {
            return null;
        }

        $metadataExtras = [];
        if ($action) {
            $metadataExtras[] = new ActionMetadataExtra($action);
        }

        return self::getContainer()->get('oro_api.metadata_provider')->getMetadata(
            $entityClass,
            Version::LATEST,
            $this->getRequestType(),
            $config,
            $metadataExtras
        );
    }
}
