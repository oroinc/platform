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
    protected function getGetRouteName()
    {
        return 'oro_rest_api_get';
    }

    /**
     * @return string
     */
    protected function getGetListRouteName()
    {
        return 'oro_rest_api_cget';
    }

    /**
     * @return string
     */
    protected function getDeleteRouteName()
    {
        return 'oro_rest_api_delete';
    }

    /**
     * @return string
     */
    protected function getDeleteListRouteName()
    {
        return 'oro_rest_api_cdelete';
    }

    /**
     * @return string
     */
    protected function getPostRouteName()
    {
        return 'oro_rest_api_post';
    }

    /**
     * @return string
     */
    protected function getPatchRouteName()
    {
        return 'oro_rest_api_patch';
    }

    /**
     * @return string
     */
    protected function getGetSubresourceRouteName()
    {
        return 'oro_rest_api_get_subresource';
    }

    /**
     * @return string
     */
    protected function getGetRelationshipRouteName()
    {
        return 'oro_rest_api_get_relationship';
    }

    /**
     * @return string
     */
    protected function getPatchRelationshipRouteName()
    {
        return 'oro_rest_api_patch_relationship';
    }

    /**
     * @return string
     */
    protected function getPostRelationshipRouteName()
    {
        return 'oro_rest_api_post_relationship';
    }

    /**
     * @return string
     */
    protected function getDeleteRelationshipRouteName()
    {
        return 'oro_rest_api_delete_relationship';
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
