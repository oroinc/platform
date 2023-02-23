<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Symfony\Component\HttpFoundation\Response;

/**
 * The base class for plain REST API functional tests.
 */
abstract class RestPlainApiTestCase extends RestApiTestCase
{
    protected const JSON_CONTENT_TYPE = 'application/json';

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
        return new RequestType([RequestType::REST]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getResponseContentType(): string
    {
        return self::JSON_CONTENT_TYPE;
    }

    /**
     * {@inheritdoc}
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

        $this->client->request(
            $method,
            $uri,
            $parameters,
            [],
            $server,
            $content
        );

        // make sure that REST API call does not start the session
        $this->assertSessionNotStarted($method, $uri, $server);

        return $this->client->getResponse();
    }

    /**
     * Asserts the response content contains the given data.
     *
     * @param array|string $expectedContent The file name or full file path to YAML template file or array
     * @param Response     $response
     * @param object|null  $entity          If not null, object will set as entity reference
     */
    protected function assertResponseContains(
        array|string $expectedContent,
        Response $response,
        object $entity = null
    ): void {
        if ($entity) {
            $this->getReferenceRepository()->addReference('entity', $entity);
        }

        $content = self::jsonToArray($response->getContent());
        $expectedContent = self::processTemplateData($this->getResponseData($expectedContent));

        self::assertThat($content, new RestPlainDocContainsConstraint($expectedContent, false));
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
            $this->assertResponseContains($expectedErrors, $response);
            if ($strict) {
                self::assertCount(
                    count($expectedErrors),
                    $content,
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
     * Extracts REST API resource identifier from the response.
     */
    protected function getResourceId(Response $response, string $identifierFieldName = 'id'): mixed
    {
        $content = self::jsonToArray($response->getContent());
        self::assertIsArray($content);
        self::assertArrayHasKey($identifierFieldName, $content);

        return $content[$identifierFieldName];
    }
}
