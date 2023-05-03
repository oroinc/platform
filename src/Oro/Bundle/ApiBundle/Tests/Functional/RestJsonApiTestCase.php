<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Yaml\Yaml;

/**
 * The base class for REST API that conforms the JSON:API specification functional tests.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class RestJsonApiTestCase extends RestApiTestCase
{
    protected const JSON_API_MEDIA_TYPE = 'application/vnd.api+json';
    protected const JSON_API_CONTENT_TYPE = 'application/vnd.api+json';

    protected function setUp(): void
    {
        $this->initClient();
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestType(): RequestType
    {
        return new RequestType([RequestType::REST, RequestType::JSON_API]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getResponseContentType(): string
    {
        return self::JSON_API_CONTENT_TYPE;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function request(
        string $method,
        string $uri,
        array $parameters = [],
        array $server = [],
        string $content = null
    ): Response {
        $this->checkTwigState();
        $this->checkHateoasHeader($server);
        $this->checkWsseAuthHeader($server);
        $this->checkCsrfHeader($server);

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
                if (str_contains($uri, '?')) {
                    $separator = '&';
                }
                $uri .= $separator . $filters;
            }
            unset($parameters['filters']);
        }

        $server['HTTP_ACCEPT'] = self::JSON_API_MEDIA_TYPE;
        if ('POST' === $method || 'PATCH' === $method || 'DELETE' === $method) {
            $server['CONTENT_TYPE'] = self::JSON_API_CONTENT_TYPE;
        } elseif (isset($server['CONTENT_TYPE'])) {
            unset($server['CONTENT_TYPE']);
        }

        $this->client->request($method, $uri, $parameters, [], $server, $content);

        // make sure that REST API call does not start the session
        $this->assertSessionNotStarted($method, $uri, $server);

        return $this->client->getResponse();
    }

    /**
     * Asserts the response content contains the given data.
     *
     * @param array|string $expectedContent The file name or full file path to YAML template file or array
     * @param Response     $response        The response object
     * @param bool         $ignoreOrder     Whether the order of elements in the primary data should not be checked
     */
    protected function assertResponseContains(
        array|string $expectedContent,
        Response $response,
        bool $ignoreOrder = false
    ): void {
        $content = self::jsonToArray($response->getContent());
        $expectedContent = $this->getResponseData($expectedContent);

        self::assertThat($content, new JsonApiDocContainsConstraint($expectedContent, false, !$ignoreOrder));
    }

    /**
     * Asserts the response contains the given number of data items.
     */
    protected static function assertResponseCount(int $expectedCount, Response $response): void
    {
        $content = self::jsonToArray($response->getContent());
        self::assertCount($expectedCount, $content[JsonApiDoc::DATA]);
    }

    /**
     * Asserts the response data are not empty.
     */
    protected static function assertResponseNotEmpty(Response $response): void
    {
        $content = self::jsonToArray($response->getContent());
        self::assertNotEmpty($content[JsonApiDoc::DATA]);
    }

    /**
     * Asserts the response content does not contain the given attributes.
     *
     * @param string[] $attributes The names of attributes
     * @param Response $response
     */
    protected function assertResponseNotHasAttributes(array $attributes, Response $response): void
    {
        $content = self::jsonToArray($response->getContent());
        self::assertArrayHasKey('data', $content);
        self::assertIsArray($content['data']);
        self::assertArrayHasKey('attributes', $content['data']);
        self::assertIsArray($content['data']['attributes']);
        foreach ($attributes as $name) {
            self::assertArrayNotHasKey($name, $content['data']['attributes']);
        }
    }

    /**
     * Asserts the response content does not contain the given relationships.
     *
     * @param string[] $relationships The names of relationships
     * @param Response $response
     */
    protected function assertResponseNotHasRelationships(array $relationships, Response $response): void
    {
        $content = self::jsonToArray($response->getContent());
        self::assertArrayHasKey('data', $content);
        self::assertIsArray($content['data']);
        self::assertArrayHasKey('relationships', $content['data']);
        self::assertIsArray($content['data']['relationships']);
        foreach ($relationships as $name) {
            self::assertArrayNotHasKey($name, $content['data']['relationships']);
        }
    }

    /**
     * Asserts that the response content contains one validation error and it is the given error.
     */
    protected function assertResponseValidationError(
        array $expectedError,
        Response $response,
        int $statusCode = Response::HTTP_BAD_REQUEST
    ): void {
        $this->assertValidationErrors([$expectedError], $response, $statusCode, true);
    }

    /**
     * Asserts response status code equals to 404 (Not Found)
     * and the response content contains "resource not accessible exception" validation error.
     */
    protected function assertResourceNotAccessibleResponse(Response $response): void
    {
        $this->assertResponseValidationError(
            ['title' => 'resource not accessible exception', 'detail' => 'The resource is not accessible.'],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Asserts response status code equals to 404 (Not Found)
     * and the response content contains "unsupported subresource" validation error.
     */
    protected function assertUnsupportedSubresourceResponse(Response $response): void
    {
        $this->assertResponseValidationError(
            ['title' => 'relationship constraint', 'detail' => 'Unsupported subresource.'],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Asserts that the response content contains the given validation error.
     */
    protected function assertResponseContainsValidationError(
        array $expectedError,
        Response $response,
        int $statusCode = Response::HTTP_BAD_REQUEST
    ): void {
        $this->assertValidationErrors([$expectedError], $response, $statusCode, false);
    }

    /**
     * Asserts that the response content contains the given validation errors and only them.
     */
    protected function assertResponseValidationErrors(
        array $expectedErrors,
        Response $response,
        int $statusCode = Response::HTTP_BAD_REQUEST
    ): void {
        $this->assertValidationErrors($expectedErrors, $response, $statusCode, true);
    }

    /**
     * Asserts that the response content contains the given validation errors.
     */
    protected function assertResponseContainsValidationErrors(
        array $expectedErrors,
        Response $response,
        int $statusCode = Response::HTTP_BAD_REQUEST
    ): void {
        $this->assertValidationErrors($expectedErrors, $response, $statusCode, false);
    }

    private function assertValidationErrors(
        array $expectedErrors,
        Response $response,
        int $statusCode,
        bool $strict
    ): void {
        static::assertResponseStatusCodeEquals($response, $statusCode);

        $content = self::jsonToArray($response->getContent());
        try {
            $this->assertResponseContains([JsonApiDoc::ERRORS => $expectedErrors], $response);
            if ($strict) {
                self::assertCount(
                    count($expectedErrors),
                    $content[JsonApiDoc::ERRORS],
                    'Unexpected number of validation errors'
                );
            }
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            throw new \PHPUnit\Framework\ExpectationFailedException(
                sprintf(
                    "%s\nResponse:\n%s",
                    $e->getMessage(),
                    json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
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
     * @param string|null $fileName The file name or full path to the output file
     *                              Also it can be NULL or empty string, in this case the response content
     *                              will be written in to the console
     * @param Response    $response
     */
    protected function dumpYmlTemplate(?string $fileName, Response $response): void
    {
        $data = self::jsonToArray($response->getContent());
        if (null === $data) {
            throw new \RuntimeException('The response does not have the content.');
        }

        if ($this->hasReferenceRepository()) {
            $idReferences = [];
            $doctrine = self::getContainer()->get('doctrine');
            $doctrineHelper = $this->getDoctrineHelper();
            $references = $this->getReferenceRepository()->getReferences();
            foreach ($references as $referenceId => $entity) {
                $entityClass = $doctrineHelper->getClass($entity);
                $entityType = ValueNormalizerUtil::tryConvertToEntityType(
                    $this->getValueNormalizer(),
                    $entityClass,
                    $this->getRequestType()
                );
                if ($entityType) {
                    $em = $doctrine->getManagerForClass($entityClass);
                    if ($em instanceof EntityManagerInterface) {
                        $metadata = $em->getClassMetadata($entityClass);
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
                $fileName = $this->getTestResourcePath($this->getResponseDataFolderName(), $fileName);
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
    protected function normalizeYmlTemplate(array &$data, array $idReferences): void
    {
        if (isset($data[JsonApiDoc::TYPE], $data[JsonApiDoc::ID])) {
            $key = $data[JsonApiDoc::TYPE] . '::' . $data[JsonApiDoc::ID];
            if (isset($idReferences[$key])) {
                [$referenceId, $entityIdFieldName] = $idReferences[$key];
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
     * {@inheritDoc}
     */
    protected static function isApplicableContentType(ResponseHeaderBag $headers): bool
    {
        return $headers->contains('Content-Type', self::JSON_API_CONTENT_TYPE);
    }

    /**
     * Extracts JSON:API resource identifier from the response.
     */
    protected function getResourceId(Response $response): string
    {
        $content = self::jsonToArray($response->getContent());
        self::assertIsArray($content);
        self::assertArrayHasKey(JsonApiDoc::DATA, $content);
        self::assertIsArray($content[JsonApiDoc::DATA]);
        self::assertArrayHasKey(JsonApiDoc::ID, $content[JsonApiDoc::DATA]);

        return $content[JsonApiDoc::DATA][JsonApiDoc::ID];
    }

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
     * Extracts the list of errors from JSON:API response.
     */
    protected function getResponseErrors(Response $response): string
    {
        $content = self::jsonToArray($response->getContent());
        self::assertIsArray($content);
        self::assertArrayHasKey(JsonApiDoc::ERRORS, $content);
        self::assertIsArray($content[JsonApiDoc::ERRORS]);

        return $content[JsonApiDoc::ERRORS];
    }
}
