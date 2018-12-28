<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Yaml\Yaml;

/**
 * The base class for REST API that conforms JSON.API specification functional tests.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class RestJsonApiTestCase extends RestApiTestCase
{
    protected const JSON_API_CONTENT_TYPE = 'application/vnd.api+json';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
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
     * {@inheritdoc}
     */
    protected function getResponseContentType()
    {
        return self::JSON_API_CONTENT_TYPE;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function request($method, $uri, array $parameters = [], array $server = [], $content = null)
    {
        $this->checkHateoasHeader($server);
        $this->checkWsseAuthHeader($server);

        if (!empty($parameters['filter'])) {
            foreach ($parameters['filter'] as $key => $filter) {
                $filter = self::processTemplateData($filter);
                if (is_array($filter)) {
                    if (ArrayUtil::isAssoc($filter)) {
                        foreach ($filter as $k => $v) {
                            if (is_array($v)) {
                                $filter[$k] = implode(',', $v);
                            } elseif (is_bool($v)) {
                                $filter[$k] = $v ? '1' : '0';
                            } elseif (!is_string($v)) {
                                $filter[$k] = (string)$v;
                            }
                        }
                    } else {
                        $filter = implode(',', $filter);
                    }
                } elseif (is_bool($filter)) {
                    $filter = $filter ? '1' : '0';
                } elseif (!is_string($filter)) {
                    $filter = (string)$filter;
                }
                $parameters['filter'][$key] = $filter;
            }
        }
        if (array_key_exists('filters', $parameters)) {
            $filters = $parameters['filters'];
            if ($filters) {
                $separator = '?';
                if (false !== strpos($uri, '?')) {
                    $separator = '&';
                }
                $uri .= $separator . $filters;
            }
            unset($parameters['filters']);
        }

        $this->client->request(
            $method,
            $uri,
            $parameters,
            [],
            array_replace($server, ['CONTENT_TYPE' => self::JSON_API_CONTENT_TYPE]),
            $content
        );

        // make sure that REST API call does not start the session
        self::assertSessionNotStarted($method, $uri);

        return $this->client->getResponse();
    }

    /**
     * Asserts the response content contains the given data.
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

        $content = self::jsonToArray($response->getContent());
        $expectedContent = self::processTemplateData($this->loadResponseData($expectedContent));

        self::assertThat($content, new JsonApiDocContainsConstraint($expectedContent, false));
    }

    /**
     * Asserts the response contains the given number of data items.
     *
     * @param int      $expectedCount
     * @param Response $response
     */
    protected static function assertResponseCount($expectedCount, Response $response)
    {
        $content = self::jsonToArray($response->getContent());
        self::assertCount($expectedCount, $content[JsonApiDoc::DATA]);
    }

    /**
     * Asserts the response data are not empty.
     *
     * @param Response $response
     */
    protected static function assertResponseNotEmpty(Response $response)
    {
        $content = self::jsonToArray($response->getContent());
        self::assertNotEmpty($content[JsonApiDoc::DATA]);
    }

    /**
     * Asserts the response content contains the given validation error.
     *
     * @param array    $expectedError
     * @param Response $response
     * @param int      $statusCode
     */
    protected function assertResponseValidationError(
        $expectedError,
        Response $response,
        $statusCode = Response::HTTP_BAD_REQUEST
    ) {
        $this->assertResponseValidationErrors([$expectedError], $response, $statusCode);
    }

    /**
     * Asserts the response content contains the given validation errors.
     *
     * @param array    $expectedErrors
     * @param Response $response
     * @param int      $statusCode
     */
    protected function assertResponseValidationErrors(
        $expectedErrors,
        Response $response,
        $statusCode = Response::HTTP_BAD_REQUEST
    ) {
        static::assertResponseStatusCodeEquals($response, $statusCode);

        $content = self::jsonToArray($response->getContent());
        try {
            $this->assertResponseContains([JsonApiDoc::ERRORS => $expectedErrors], $response);
            self::assertCount(
                count($expectedErrors),
                $content[JsonApiDoc::ERRORS],
                'Unexpected number of validation errors'
            );
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            throw new \PHPUnit\Framework\ExpectationFailedException(
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
        $data = self::jsonToArray($response->getContent());
        if (null === $data) {
            throw new \RuntimeException('The response does not have the content.');
        }

        if ($this->hasReferenceRepository()) {
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
        }

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
        if (isset($data[JsonApiDoc::TYPE], $data[JsonApiDoc::ID])) {
            $key = $data[JsonApiDoc::TYPE] . '::' . $data[JsonApiDoc::ID];
            if (isset($idReferences[$key])) {
                list($referenceId, $entityIdFieldName) = $idReferences[$key];
                $data[JsonApiDoc::ID] = sprintf('<toString(@%s->%s)>', $referenceId, $entityIdFieldName);
                if (isset($data[JsonApiDoc::ATTRIBUTES])) {
                    $attributes = $data[JsonApiDoc::ATTRIBUTES];
                    $dateFields = ['createdAt', 'updatedAt'];
                    foreach ($dateFields as $field) {
                        if (isset($attributes[$field])) {
                            $data[JsonApiDoc::ATTRIBUTES][$field] = sprintf(
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
     * Extracts JSON.API resource identifier from the response.
     *
     * @param Response $response
     *
     * @return string
     */
    protected function getResourceId(Response $response)
    {
        $content = self::jsonToArray($response->getContent());
        self::assertInternalType('array', $content);
        self::assertArrayHasKey(JsonApiDoc::DATA, $content);
        self::assertInternalType('array', $content[JsonApiDoc::DATA]);
        self::assertArrayHasKey(JsonApiDoc::ID, $content[JsonApiDoc::DATA]);

        return $content[JsonApiDoc::DATA][JsonApiDoc::ID];
    }

    /**
     * @param Response $response
     * @param string   $includeId
     *
     * @return string
     */
    protected static function getNewResourceIdFromIncludedSection(Response $response, string $includeId): string
    {
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayHasKey('included', $responseContent);
        foreach ($responseContent['included'] as $item) {
            if (isset($item['meta']['includeId']) && $item['meta']['includeId'] === $includeId) {
                return $item['id'];
            }
        }
        self::fail(sprintf('New resource "%s" was not found.', $includeId));
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
        $content = self::jsonToArray($response->getContent());
        self::assertInternalType('array', $content);
        self::assertArrayHasKey(JsonApiDoc::ERRORS, $content);
        self::assertInternalType('array', $content[JsonApiDoc::ERRORS]);

        return $content[JsonApiDoc::ERRORS];
    }
}
