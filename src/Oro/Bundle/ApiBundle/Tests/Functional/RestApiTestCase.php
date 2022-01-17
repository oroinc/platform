<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\Extra\ActionMetadataExtra;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * The base class for different kind of REST API functional tests.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class RestApiTestCase extends ApiTestCase
{
    protected const API_TEST_STATEFUL_REQUEST = '_api_test_stateful';

    protected function getWsseAuthHeader(): array
    {
        return self::generateWsseAuthHeader(static::USER_NAME, static::USER_PASSWORD);
    }

    protected function getItemRouteName(): string
    {
        return 'oro_rest_api_item';
    }

    protected function getListRouteName(): string
    {
        return 'oro_rest_api_list';
    }

    protected function getSubresourceRouteName(): string
    {
        return 'oro_rest_api_subresource';
    }

    protected function getRelationshipRouteName(): string
    {
        return 'oro_rest_api_relationship';
    }

    /**
     * Returns the base URL for all REST API requests, e.g. "http://localhost/api".
     */
    protected function getApiBaseUrl(): string
    {
        return substr($this->getUrl($this->getListRouteName(), ['entity' => 'test'], true), 0, -5);
    }

    abstract protected function getResponseContentType(): string;

    /**
     * Sends REST API request.
     */
    abstract protected function request(
        string $method,
        string $uri,
        array $parameters = [],
        array $server = [],
        string $content = null
    ): Response;

    protected function checkWsseAuthHeader(array &$server): void
    {
        if (!array_key_exists('HTTP_X-WSSE', $server)) {
            $server = array_replace($server, $this->getWsseAuthHeader());
        } elseif (!$server['HTTP_X-WSSE']) {
            unset($server['HTTP_X-WSSE']);
        }
    }

    protected function checkCsrfHeader(array &$server): void
    {
        $csrfHeader = 'HTTP_' . CsrfRequestManager::CSRF_HEADER;
        $cookieJar = $this->client->getCookieJar();
        $csrfCookie = $cookieJar->get(CsrfRequestManager::CSRF_TOKEN_ID);
        if (array_key_exists($csrfHeader, $server)) {
            if (null === $csrfCookie) {
                $cookieJar->set(new Cookie(CsrfRequestManager::CSRF_TOKEN_ID, $server[$csrfHeader]));
            }
        } elseif (null !== $csrfCookie) {
            $server[$csrfHeader] = $csrfCookie->getValue();
        }
        if (array_key_exists($csrfHeader, $server)
            && !array_key_exists('HTTP_SESSION', $server)
            && !$this->isStatelessRequest($server)
        ) {
            $server['HTTP_SESSION'] = 'test_session';
        }
    }

    protected function checkHateoasHeader(array &$server): void
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
     * Sends GET request for a list of entities.
     */
    protected function cget(
        array $routeParameters = [],
        array $parameters = [],
        array $server = [],
        bool $assertValid = true
    ): Response {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'GET',
            $this->getUrl($this->getListRouteName(), $routeParameters),
            $parameters,
            $server
        );

        $this->clearEntityManager();

        if ($assertValid) {
            $entityType = self::extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals(
                $response,
                Response::HTTP_OK,
                $entityType,
                'get list'
            );
            self::assertResponseContentTypeEquals($response, $this->getResponseContentType());
        }

        return $response;
    }

    /**
     * Sends DELETE request for a list of entities.
     */
    protected function cdelete(
        array $routeParameters = [],
        array $parameters = [],
        array $server = [],
        bool $assertValid = true
    ): Response {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'DELETE',
            $this->getUrl($this->getListRouteName(), $routeParameters),
            $parameters,
            $server
        );

        $this->clearEntityManager();

        if ($assertValid) {
            $entityType = self::extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals(
                $response,
                Response::HTTP_NO_CONTENT,
                $entityType,
                'delete list'
            );
        }

        return $response;
    }

    /**
     * Sends GET request for a single entity.
     */
    protected function get(
        array $routeParameters = [],
        array $parameters = [],
        array $server = [],
        bool $assertValid = true
    ): Response {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'GET',
            $this->getUrl($this->getItemRouteName(), $routeParameters),
            $parameters,
            $server
        );

        $this->clearEntityManager();

        if ($assertValid) {
            $entityType = self::extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals(
                $response,
                Response::HTTP_OK,
                $entityType,
                'get'
            );
            self::assertResponseContentTypeEquals($response, $this->getResponseContentType());
        }

        return $response;
    }

    /**
     * Sends PATCH request for a list of entities.
     */
    protected function cpatch(
        array $routeParameters = [],
        array|string $parameters = [],
        array $server = [],
        bool $assertValid = true
    ): Response {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = $this->getRequestData($parameters);
        $content = null;
        if ($parameters) {
            $content = json_encode($parameters, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        }
        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_api_list', $routeParameters),
            [],
            $server,
            $content
        );

        $this->clearEntityManager();

        if ($assertValid) {
            $entityType = self::extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals(
                $response,
                Response::HTTP_ACCEPTED,
                $entityType,
                'patch list'
            );
            self::assertResponseContentTypeEquals($response, $this->getResponseContentType());
        }

        return $response;
    }

    /**
     * Sends PATCH request for a single entity.
     */
    protected function patch(
        array $routeParameters = [],
        array|string $parameters = [],
        array $server = [],
        bool $assertValid = true
    ): Response {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = $this->getRequestData($parameters);
        $response = $this->request(
            'PATCH',
            $this->getUrl($this->getItemRouteName(), $routeParameters),
            $parameters,
            $server
        );

        $this->clearEntityManager();

        if ($assertValid) {
            $entityType = self::extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals(
                $response,
                Response::HTTP_OK,
                $entityType,
                'patch'
            );
            self::assertResponseContentTypeEquals($response, $this->getResponseContentType());
        }

        return $response;
    }

    /**
     * Sends POST request for an entity resource.
     */
    protected function post(
        array $routeParameters = [],
        array|string $parameters = [],
        array $server = [],
        bool $assertValid = true
    ): Response {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = $this->getRequestData($parameters);
        $response = $this->request(
            'POST',
            $this->getUrl($this->getListRouteName(), $routeParameters),
            $parameters,
            $server
        );

        $this->clearEntityManager();

        if ($assertValid) {
            $entityType = self::extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals(
                $response,
                Response::HTTP_CREATED,
                $entityType,
                'post'
            );
            self::assertResponseContentTypeEquals($response, $this->getResponseContentType());
        }

        return $response;
    }

    /**
     * Sends DELETE request for a single entity.
     */
    protected function delete(
        array $routeParameters = [],
        array $parameters = [],
        array $server = [],
        bool $assertValid = true
    ): Response {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'DELETE',
            $this->getUrl($this->getItemRouteName(), $routeParameters),
            $parameters,
            $server
        );

        $this->clearEntityManager();

        if ($assertValid) {
            $entityType = self::extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals(
                $response,
                Response::HTTP_NO_CONTENT,
                $entityType,
                'delete'
            );
        }

        return $response;
    }

    /**
     * Sends GET request for a relationship of a single entity.
     */
    protected function getRelationship(
        array $routeParameters = [],
        array $parameters = [],
        array $server = [],
        bool $assertValid = true
    ): Response {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'GET',
            $this->getUrl($this->getRelationshipRouteName(), $routeParameters),
            $parameters,
            $server
        );

        $this->clearEntityManager();

        if ($assertValid) {
            $entityType = self::extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals(
                $response,
                Response::HTTP_OK,
                $entityType,
                'get relationship'
            );
            self::assertResponseContentTypeEquals($response, $this->getResponseContentType());
        }

        return $response;
    }

    /**
     * Sends PATCH request for a relationship of a single entity.
     */
    protected function patchRelationship(
        array $routeParameters = [],
        array $parameters = [],
        array $server = [],
        bool $assertValid = true
    ): Response {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'PATCH',
            $this->getUrl($this->getRelationshipRouteName(), $routeParameters),
            $parameters,
            $server
        );

        $this->clearEntityManager();

        if ($assertValid) {
            $entityType = self::extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals(
                $response,
                Response::HTTP_NO_CONTENT,
                $entityType,
                'patch relationship'
            );
        }

        return $response;
    }

    /**
     * Sends POST request for a relationship of a single entity.
     */
    protected function postRelationship(
        array $routeParameters = [],
        array $parameters = [],
        array $server = [],
        bool $assertValid = true
    ): Response {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'POST',
            $this->getUrl($this->getRelationshipRouteName(), $routeParameters),
            $parameters,
            $server
        );

        $this->clearEntityManager();

        if ($assertValid) {
            $entityType = self::extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals(
                $response,
                Response::HTTP_NO_CONTENT,
                $entityType,
                'post relationship'
            );
        }

        return $response;
    }

    /**
     * Sends DELETE request for a relationship of a single entity.
     */
    protected function deleteRelationship(
        array $routeParameters = [],
        array $parameters = [],
        array $server = [],
        bool $assertValid = true
    ): Response {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'DELETE',
            $this->getUrl($this->getRelationshipRouteName(), $routeParameters),
            $parameters,
            $server
        );

        $this->clearEntityManager();

        if ($assertValid) {
            $entityType = self::extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals(
                $response,
                Response::HTTP_NO_CONTENT,
                $entityType,
                'delete relationship'
            );
        }

        return $response;
    }

    /**
     * Sends GET request for a sub-resource of a single entity.
     */
    protected function getSubresource(
        array $routeParameters = [],
        array $parameters = [],
        array $server = [],
        bool $assertValid = true
    ): Response {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'GET',
            $this->getUrl($this->getSubresourceRouteName(), $routeParameters),
            $parameters,
            $server
        );

        $this->clearEntityManager();

        if ($assertValid) {
            $entityType = self::extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals(
                $response,
                Response::HTTP_OK,
                $entityType,
                'get subresource'
            );
            self::assertResponseContentTypeEquals($response, $this->getResponseContentType());
        }

        return $response;
    }

    /**
     * Sends PATCH request for a sub-resource of a single entity.
     */
    protected function patchSubresource(
        array $routeParameters = [],
        array|string $parameters = [],
        array $server = [],
        bool $assertValid = true
    ): Response {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = $this->getRequestData($parameters);
        $response = $this->request(
            'PATCH',
            $this->getUrl($this->getSubresourceRouteName(), $routeParameters),
            $parameters,
            $server
        );

        $this->clearEntityManager();

        if ($assertValid) {
            $entityType = self::extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals(
                $response,
                [Response::HTTP_OK, Response::HTTP_NO_CONTENT],
                $entityType,
                'patch subresource'
            );
        }

        return $response;
    }

    /**
     * Sends POST request for a sub-resource of a single entity.
     */
    protected function postSubresource(
        array $routeParameters = [],
        array|string $parameters = [],
        array $server = [],
        bool $assertValid = true
    ): Response {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = $this->getRequestData($parameters);
        $response = $this->request(
            'POST',
            $this->getUrl($this->getSubresourceRouteName(), $routeParameters),
            $parameters,
            $server
        );

        $this->clearEntityManager();

        if ($assertValid) {
            $entityType = self::extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals(
                $response,
                [Response::HTTP_OK, Response::HTTP_CREATED, Response::HTTP_NO_CONTENT],
                $entityType,
                'post subresource'
            );
        }

        return $response;
    }

    /**
     * Sends DELETE request for a sub-resource of a single entity.
     */
    protected function deleteSubresource(
        array $routeParameters = [],
        array|string $parameters = [],
        array $server = [],
        bool $assertValid = true
    ): Response {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = $this->getRequestData($parameters);
        $response = $this->request(
            'DELETE',
            $this->getUrl($this->getSubresourceRouteName(), $routeParameters),
            $parameters,
            $server
        );

        $this->clearEntityManager();

        if ($assertValid) {
            $entityType = self::extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals(
                $response,
                [Response::HTTP_OK, Response::HTTP_NO_CONTENT],
                $entityType,
                'delete subresource'
            );
        }

        return $response;
    }

    /**
     * Sends OPTIONS request.
     */
    protected function options(
        string $routeName,
        array $routeParameters = [],
        array $server = [],
        bool $assertValid = true
    ): Response {
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
            self::assertSame('0', $response->headers->get('Content-Length'));
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

    protected function assertOptionsResponseCacheHeader(Response $response): void
    {
        self::assertResponseHeader($response, 'Cache-Control', 'max-age=600, public');
        self::assertResponseHeader($response, 'Vary', 'Origin');
    }

    protected function getRestApiEntityId(string $entityClass, mixed $entityId): ?string
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

    protected function isStatelessRequest(array $server): bool
    {
        return
            !array_key_exists('HTTP_' . CsrfRequestManager::CSRF_HEADER, $server)
            || null === $this->client->getCookieJar()->get(self::API_TEST_STATEFUL_REQUEST);
    }

    protected function assertSessionNotStarted(string $method, string $uri, array $server): void
    {
        // do not check session if request was made as a session aware AJAX request
        if (!$this->isStatelessRequest($server)) {
            return;
        }

        self::assertFalse(
            self::getContainer()->get('oro_api.tests.test_session_listener')->isSessionStarted(),
            sprintf(
                'The Session must not be started because REST API is stateless. Request: %s %s',
                $method,
                $uri
            )
        );
    }

    private static function extractEntityType(array $parameters): string
    {
        if (empty($parameters['entity'])) {
            return 'unknown';
        }

        return $parameters['entity'];
    }
}
