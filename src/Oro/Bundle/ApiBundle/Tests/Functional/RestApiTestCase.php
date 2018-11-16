<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\ActionMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\Version;
use Symfony\Component\HttpFoundation\Response;

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
     * Returns the base URL for all REST API requests, e.g. "http://localhost/api".
     *
     * @return string
     */
    protected function getApiBaseUrl()
    {
        return substr($this->getUrl($this->getListRouteName(), ['entity' => 'test'], true), 0, -5);
    }

    /**
     * Sends REST API request.
     *
     * @param string      $method
     * @param string      $uri
     * @param array       $parameters
     * @param array       $server
     * @param string|null $content
     *
     * @return Response
     */
    abstract protected function request($method, $uri, array $parameters = [], array $server = [], $content = null);

    /**
     * @param array $server
     */
    protected function checkWsseAuthHeader(array &$server)
    {
        if (!array_key_exists('HTTP_X-WSSE', $server)) {
            $server = array_replace($server, $this->getWsseAuthHeader());
        } elseif (!$server['HTTP_X-WSSE']) {
            unset($server['HTTP_X-WSSE']);
        }
    }

    /**
     * @param array $server
     */
    protected function checkHateoasHeader(array &$server)
    {
        $isHateoasEnabled = false;
        if (array_key_exists('HTTP_HATEOAS', $server)) {
            $isHateoasEnabled = (bool)$server['HTTP_HATEOAS'];
            unset($server['HTTP_HATEOAS']);
        }
        if (!$isHateoasEnabled) {
            $xInclude = '';
            if (array_key_exists('HTTP_X-Include', $server)) {
                $xInclude = $server['HTTP_X-Include'];
            }
            if ($xInclude) {
                $xInclude .= ';';
            }
            $xInclude .= 'noHateoas';
            $server['HTTP_X-Include'] = $xInclude;
        }
    }

    /**
     * Sends OPTIONS request.
     *
     * @param string $routeName
     * @param array  $routeParameters
     * @param array  $parameters
     * @param array  $server
     * @param bool   $assertValid
     *
     * @return Response
     */
    protected function options(
        string $routeName,
        array $routeParameters = [],
        array $server = [],
        $assertValid = true
    ) {
        if (!array_key_exists('HTTP_X-WSSE', $server)) {
            // disables authentication because OPTIONS requests must not require it
            $server['HTTP_X-WSSE'] = null;
        }

        $response = $this->request(
            'OPTIONS',
            $this->getUrl($routeName, self::processTemplateData($routeParameters)),
            [],
            $server
        );

        if ($assertValid) {
            self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
            self::assertSame('', $response->getContent());
            self::assertSame(0, $response->headers->get('Content-Length'));
            $this->assertOptionsResponseCacheHeader($response);
        }

        if (!$server['HTTP_X-WSSE']) {
            self::assertTrue(
                null === self::getContainer()->get('security.token_storage')->getToken(),
                'The security token must not be initialized for OPTIONS request'
            );
        }

        return $response;
    }

    /**
     * @param Response $response
     */
    protected function assertOptionsResponseCacheHeader(Response $response)
    {
        self::assertResponseHeader($response, 'Cache-Control', 'max-age=600, public');
        self::assertResponseHeader($response, 'Vary', 'Origin');
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
