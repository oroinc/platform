<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Yaml\Yaml;

use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class RestJsonApiTestCase extends ApiTestCase
{
    const JSON_API_CONTENT_TYPE = 'application/vnd.api+json';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            array_replace(
                $this->generateWsseAuthHeader(),
                ['CONTENT_TYPE' => self::JSON_API_CONTENT_TYPE]
            )
        );

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestType()
    {
        return new RequestType([RequestType::REST, RequestType::JSON_API]);
    }

    /**
     * Sends REST API request.
     *
     * @param string $method
     * @param string $uri
     * @param array  $parameters
     * @param array  $server
     *
     * @return Response
     */
    protected function request($method, $uri, array $parameters = [], array $server = [])
    {
        if (!empty($parameters['filter'])) {
            foreach ($parameters['filter'] as $key => $filter) {
                $filter = self::processTemplateData($filter);
                if (is_array($filter)) {
                    if (ArrayUtil::isAssoc($filter)) {
                        foreach ($filter as $k => $v) {
                            if (is_array($v)) {
                                $filter[$k] = implode(',', $v);
                            }
                        }
                    } else {
                        $filter = implode(',', $filter);
                    }
                }
                $parameters['filter'][$key] = $filter;
            }
        }

        $this->client->request(
            $method,
            $uri,
            $parameters,
            [],
            array_replace($server, ['CONTENT_TYPE' => self::JSON_API_CONTENT_TYPE])
        );

        return $this->client->getResponse();
    }

    /**
     * Sends GET request for a single entity.
     *
     * @param array $routeParameters
     * @param array $parameters
     * @param array $server
     * @param bool  $assertValid
     *
     * @return Response
     */
    protected function get(
        array $routeParameters = [],
        array $parameters = [],
        array $server = [],
        $assertValid = true
    ) {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_get', $routeParameters),
            $parameters,
            $server
        );

        if ($assertValid) {
            $entityType = $this->extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get');
            self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        }

        return $response;
    }

    /**
     * Sends GET request for a relationship of a single entity.
     *
     * @param array $routeParameters
     * @param array $parameters
     * @param array $server
     * @param bool  $assertValid
     *
     * @return Response
     */
    protected function getRelationship(
        array $routeParameters = [],
        array $parameters = [],
        array $server = [],
        $assertValid = true
    ) {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_get_relationship', $routeParameters),
            $parameters,
            $server
        );

        if ($assertValid) {
            $entityType = $this->extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get relationship');
            self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        }

        return $response;
    }

    /**
     * Sends GET request for a sub-resource of a single entity.
     *
     * @param array $routeParameters
     * @param array $parameters
     * @param array $server
     * @param bool  $assertValid
     *
     * @return Response
     */
    protected function getSubresource(
        array $routeParameters = [],
        array $parameters = [],
        array $server = [],
        $assertValid = true
    ) {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_get_subresource', $routeParameters),
            $parameters,
            $server
        );

        if ($assertValid) {
            $entityType = $this->extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get subresource');
            self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        }

        return $response;
    }

    /**
     * Sends GET request for a list of entities.
     *
     * @param array $routeParameters
     * @param array $parameters
     * @param array $server
     * @param bool  $assertValid
     *
     * @return Response
     */
    protected function cget(
        array $routeParameters = [],
        array $parameters = [],
        array $server = [],
        $assertValid = true
    ) {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', $routeParameters),
            $parameters,
            $server
        );

        if ($assertValid) {
            $entityType = $this->extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get list');
            self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        }

        return $response;
    }

    /**
     * Sends POST request for an entity resource.
     *
     * @param array $routeParameters
     * @param array|string $parameters
     * @param array $server
     * @param bool  $assertValid
     *
     * @return Response
     */
    protected function post(
        array $routeParameters = [],
        $parameters = [],
        array $server = [],
        $assertValid = true
    ) {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = $this->getRequestData($parameters);
        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', $routeParameters),
            $parameters,
            $server
        );

        $this->getEntityManager()->clear();

        if ($assertValid) {
            $entityType = $this->extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals($response, Response::HTTP_CREATED, $entityType, 'post');
            self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        }

        return $response;
    }

    /**
     * Sends POST request for a relationship of a single entity.
     *
     * @param array $routeParameters
     * @param array $parameters
     * @param array $server
     * @param bool  $assertValid
     *
     * @return Response
     */
    protected function postRelationship(
        array $routeParameters = [],
        array $parameters = [],
        array $server = [],
        $assertValid = true
    ) {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post_relationship', $routeParameters),
            $parameters,
            $server
        );

        $this->getEntityManager()->clear();

        if ($assertValid) {
            $entityType = $this->extractEntityType($routeParameters);
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
     * Sends PATCH request for a single entity.
     *
     * @param array        $routeParameters
     * @param array|string $parameters
     * @param array $server
     * @param bool  $assertValid
     *
     * @return Response
     */
    protected function patch(
        array $routeParameters = [],
        $parameters = [],
        array $server = [],
        $assertValid = true
    ) {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = $this->getRequestData($parameters);
        $response = $this->request(
            'PATCH',
            $this->getUrl(
                'oro_rest_api_patch',
                $routeParameters
            ),
            $parameters,
            $server
        );

        $this->getEntityManager()->clear();

        if ($assertValid) {
            $entityType = $this->extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'patch');
            self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        }

        return $response;
    }

    /**
     * Sends PATCH request for a relationship of a single entity.
     *
     * @param array $routeParameters
     * @param array $parameters
     * @param array $server
     * @param bool  $assertValid
     *
     * @return Response
     */
    protected function patchRelationship(
        array $routeParameters = [],
        array $parameters = [],
        array $server = [],
        $assertValid = true
    ) {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_api_patch_relationship', $routeParameters),
            $parameters,
            $server
        );

        $this->getEntityManager()->clear();

        if ($assertValid) {
            $entityType = $this->extractEntityType($routeParameters);
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
     * Sends DELETE request for a single entity.
     *
     * @param array $routeParameters
     * @param array $parameters
     * @param array $server
     * @param bool  $assertValid
     *
     * @return Response
     */
    protected function delete(
        array $routeParameters = [],
        array $parameters = [],
        array $server = [],
        $assertValid = true
    ) {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'DELETE',
            $this->getUrl('oro_rest_api_delete', $routeParameters),
            $parameters,
            $server
        );

        $this->getEntityManager()->clear();

        if ($assertValid) {
            $entityType = $this->extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT, $entityType, 'delete');
        }

        return $response;
    }

    /**
     * Sends DELETE request for a list of entities.
     *
     * @param array $routeParameters
     * @param array $parameters
     * @param array $server
     * @param bool  $assertValid
     *
     * @return Response
     */
    protected function cdelete(
        array $routeParameters = [],
        array $parameters = [],
        array $server = [],
        $assertValid = true
    ) {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'DELETE',
            $this->getUrl('oro_rest_api_cdelete', $routeParameters),
            $parameters,
            $server
        );

        $this->getEntityManager()->clear();

        if ($assertValid) {
            $entityType = $this->extractEntityType($routeParameters);
            self::assertApiResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT, $entityType, 'delete list');
        }

        return $response;
    }

    /**
     * Sends DELETE request for a relationship of a single entity.
     *
     * @param array $routeParameters
     * @param array $parameters
     * @param array $server
     * @param bool  $assertValid
     *
     * @return Response
     */
    protected function deleteRelationship(
        array $routeParameters = [],
        array $parameters = [],
        array $server = [],
        $assertValid = true
    ) {
        $routeParameters = self::processTemplateData($routeParameters);
        $parameters = self::processTemplateData($parameters);
        $response = $this->request(
            'DELETE',
            $this->getUrl('oro_rest_api_delete_relationship', $routeParameters),
            $parameters,
            $server
        );

        $this->getEntityManager()->clear();

        if ($assertValid) {
            $entityType = $this->extractEntityType($routeParameters);
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
     * Asserts the response content contains the the given data.
     *
     * @param array|string $expectedContent The file name or full file path to YAML template file or array
     *
     * @return array
     */
    protected function loadResponseData($expectedContent)
    {
        if (is_string($expectedContent)) {
            if ($this->isRelativePath($expectedContent)) {
                $expectedContent = $this->getTestResourcePath('responses', $expectedContent);
            }
            $file = $this->getContainer()->get('file_locator')->locate($expectedContent);
            self::assertTrue(is_file($file), sprintf('File "%s" with expected content not found', $expectedContent));

            $expectedContent = Yaml::parse(file_get_contents($file));
        }

        return self::processTemplateData($expectedContent);
    }

    /**
     * Asserts the response content contains the the given data.
     *
     * @param array|string $expectedContent The file name or full file path to YAML template file or array
     * @param Response     $response
     * @param object|null  $entity          If not null, object will set as entity reference
     */
    protected function assertResponseContains($expectedContent, Response $response, $entity = null)
    {
        if ($entity) {
            $this->getReferenceRepository()->addReference('entity', $entity);
        }

        $content = json_decode($response->getContent(), true);
        $expectedContent = self::processTemplateData($this->loadResponseData($expectedContent));

        self::assertArrayContains($expectedContent, $content);

        // test the primary data collection count and order
        if (!empty($expectedContent[JsonApiDoc::DATA])) {
            $expectedData = $expectedContent[JsonApiDoc::DATA];
            if (is_array($expectedData) && isset($expectedData[0][JsonApiDoc::TYPE])) {
                $expectedItems = $this->getResponseDataItems($expectedData);
                $actualItems = $this->getResponseDataItems($content[JsonApiDoc::DATA]);
                self::assertSame(
                    $expectedItems,
                    $actualItems,
                    'Failed asserting the primary data collection items count and order.'
                );
            }
        }
    }

    /**
     * @param array $data
     *
     * @return array [['type' => entity type, 'id' => entity id], ...]
     */
    private function getResponseDataItems(array $data)
    {
        $result = [];
        foreach ($data as $item) {
            $result[] = ['type' => $item[JsonApiDoc::TYPE], 'id' => $item[JsonApiDoc::ID]];
        }

        return $result;
    }
    /**
     * Asserts the response contains the given number of data items.
     *
     * @param int      $expectedCount
     * @param Response $response
     */
    protected static function assertResponseCount($expectedCount, Response $response)
    {
        $content = json_decode($response->getContent(), true);
        self::assertCount($expectedCount, $content['data']);
    }

    /**
     * Asserts the response data are not empty.
     *
     * @param Response $response
     */
    protected static function assertResponseNotEmpty(Response $response)
    {
        $content = json_decode($response->getContent(), true);
        self::assertNotEmpty($content['data']);
    }

    /**
     * Asserts the response content contains the the given validation error.
     *
     * @param array    $expectedError
     * @param Response $response
     */
    protected function assertResponseValidationError($expectedError, Response $response)
    {
        $this->assertResponseValidationErrors([$expectedError], $response);
    }

    /**
     * Asserts the response content contains the the given validation errors.
     *
     * @param array    $expectedErrors
     * @param Response $response
     */
    protected function assertResponseValidationErrors($expectedErrors, Response $response)
    {
        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);

        $content = json_decode($response->getContent(), true);
        try {
            $this->assertResponseContains(['errors' => $expectedErrors], $response);
            self::assertCount(
                count($expectedErrors),
                $content['errors'],
                'Unexpected number of validation errors'
            );
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            throw new \PHPUnit_Framework_ExpectationFailedException(
                sprintf(
                    "%s\nResponse:\n%s",
                    $e->getMessage(),
                    json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                ),
                $e->getComparisonFailure()
            );
        }
    }

    /**
     * Saves a response content to a YAML file.
     * If the first parameter is a file name, the file will be saved in the `responses` directory
     * near to PHP file contains the test.
     *
     * @param string   $fileName The file name or full path to the output file
     *                           Also it can be NULL or empty string, in this case the response content
     *                           will be written in to the console
     * @param Response $response
     */
    protected function dumpYmlTemplate($fileName, Response $response)
    {
        $data = json_decode($response->getContent(), true);
        if (null === $data) {
            throw new \RuntimeException('The response does not have the content.');
        }

        $idReferences = [];
        $references = $this->getReferenceRepository()->getReferences();
        foreach ($references as $referenceId => $entity) {
            $entityClass = ClassUtils::getClass($entity);
            $entityType = $this->getEntityType($entityClass, false);
            if ($entityType) {
                $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass, false);
                if (null !== $metadata) {
                    $entityId = $metadata->getIdentifierValues($entity);
                    if (count($entityId) === 1) {
                        $entityId = (string)reset($entityId);
                        $idReferences[$entityType . '::' . $entityId] = [
                            $referenceId,
                            $metadata->getSingleIdentifierFieldName()
                        ];
                    }
                }
            }
        }

        $this->normalizeYmlTemplate($data, $idReferences);

        $content = Yaml::dump($data, 8);
        // replace "data: {}" with "data: []" to correct representation of empty collection
        $content = preg_replace('/(\s+data: ){\s*}$/m', '$1[]', $content);

        if ($fileName) {
            if ($this->isRelativePath($fileName)) {
                $fileName = $this->getTestResourcePath('responses', $fileName);
            }
            file_put_contents($fileName, $content);
        } else {
            echo "\n" . $content;
        }
    }

    /**
     * @param array $data
     * @param array $idReferences ['entityType::entityId' => [referenceId, entityIdFieldName], ...]
     */
    protected function normalizeYmlTemplate(array &$data, array $idReferences)
    {
        if (isset($data['type']) && isset($data['id'])) {
            $key = $data['type'] . '::' . $data['id'];
            if (isset($idReferences[$key])) {
                list($referenceId, $entityIdFieldName) = $idReferences[$key];
                $data['id'] = sprintf('<toString(@%s->%s)>', $referenceId, $entityIdFieldName);
                if (isset($data['attributes'])) {
                    $attributes = $data['attributes'];
                    $dateFields = ['createdAt', 'updatedAt', 'created', 'updated'];
                    foreach ($dateFields as $field) {
                        if (isset($attributes[$field])) {
                            $data['attributes'][$field] = sprintf(
                                '@%s->%s->format("Y-m-d\TH:i:s\Z")',
                                $referenceId,
                                $field
                            );
                        }
                    }
                }
            }
        }
        foreach ($data as &$value) {
            if (is_array($value)) {
                $this->normalizeYmlTemplate($value, $idReferences);
            }
        }
    }

    /**
     * @param ResponseHeaderBag $headers
     *
     * @return bool
     */
    protected static function isApplicableContentType(ResponseHeaderBag $headers)
    {
        return $headers->contains('Content-Type', self::JSON_API_CONTENT_TYPE);
    }

    /**
     * Converts the given request to an array that can be sent to the server.
     *
     * @param array|string $request
     *
     * @return array
     */
    protected function getRequestData($request)
    {
        if (is_string($request) && $this->isRelativePath($request)) {
            $request = $this->getTestResourcePath('requests', $request);
        }

        return self::processTemplateData($request);
    }

    /**
     * Extracts JSON.API resource identifier from the response.
     *
     * @param Response $response
     *
     * @return string
     */
    protected function getResourceId(Response $response)
    {
        $content = json_decode($response->getContent(), true);
        self::assertInternalType('array', $content);
        self::assertArrayHasKey('data', $content);
        self::assertInternalType('array', $content['data']);
        self::assertArrayHasKey('id', $content['data']);

        return $content['data']['id'];
    }

    /**
     * Extracts the list of errors from JSON.API response.
     *
     * @param Response $response
     *
     * @return string
     */
    protected function getResponseErrors(Response $response)
    {
        $content = json_decode($response->getContent(), true);
        self::assertInternalType('array', $content);
        self::assertArrayHasKey('errors', $content);
        self::assertInternalType('array', $content['errors']);

        return $content['errors'];
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    private function extractEntityType(array $parameters)
    {
        if (empty($parameters['entity'])) {
            return 'unknown';
        }

        return $parameters['entity'];
    }
}
