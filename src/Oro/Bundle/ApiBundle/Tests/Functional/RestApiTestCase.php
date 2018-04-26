<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
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
    protected function getRestApiEntityId($entityClass, $entityId)
    {
        if (null === $entityId) {
            return null;
        }

        $config = self::getContainer()->get('oro_api.config_provider')->getConfig(
            $entityClass,
            Version::LATEST,
            $this->getRequestType(),
            [new EntityDefinitionConfigExtra(), new FilterIdentifierFieldsConfigExtra()]
        );
        $metadata = self::getContainer()->get('oro_api.metadata_provider')->getMetadata(
            $entityClass,
            Version::LATEST,
            $this->getRequestType(),
            $config->getDefinition()
        );

        return self::getContainer()->get('oro_api.rest.entity_id_transformer')
            ->transform($entityId, $metadata);
    }
}
