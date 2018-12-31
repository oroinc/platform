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
        return new RequestType([RequestType::REST]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getResponseContentType()
    {
        return self::JSON_CONTENT_TYPE;
    }

    /**
     * {@inheritdoc}
     */
    protected function request($method, $uri, array $parameters = [], array $server = [], $content = null)
    {
        $this->checkHateoasHeader($server);
        $this->checkWsseAuthHeader($server);

        $this->client->request(
            $method,
            $uri,
            $parameters,
            [],
            $server,
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

        self::assertThat($content, new RestPlainDocContainsConstraint($expectedContent, false));
    }

    /**
     * Asserts the response content contains the given validation error.
     *
     * @param array    $expectedError
     * @param Response $response
     */
    protected function assertResponseValidationError($expectedError, Response $response)
    {
        $this->assertResponseValidationErrors([$expectedError], $response);
    }

    /**
     * Asserts the response content contains the given validation errors.
     *
     * @param array    $expectedErrors
     * @param Response $response
     */
    protected function assertResponseValidationErrors($expectedErrors, Response $response)
    {
        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);

        $content = self::jsonToArray($response->getContent());
        try {
            $this->assertResponseContains($expectedErrors, $response);
            self::assertCount(
                count($expectedErrors),
                $content,
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
     * Extracts REST API resource identifier from the response.
     *
     * @param Response $response
     * @param string   $identifierFieldName
     *
     * @return mixed
     */
    protected function getResourceId(Response $response, string $identifierFieldName = 'id')
    {
        $content = self::jsonToArray($response->getContent());
        self::assertInternalType('array', $content);
        self::assertArrayHasKey($identifierFieldName, $content);

        return $content[$identifierFieldName];
    }
}
