<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AbstractTestCase extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([]);
    }

    /**
     * Makes request to REST API resource and verifies the response is expected.
     *
     * Example:
     * <code>
     *  $this->sendRestApiRequest(
     *      [
     *          'method' => 'POST', // One of 'POST', 'GET', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'
     *          'url' => $this->getUrl('oro_api_post_foobar'),
     *          'route' => 'oro_api_post_foobar', // name of route to generate the url, used if url is not passed
     *          'routeParameters' => ['foo' => 'bar'], // parameters to generate the url, used if url is not passed
     *          'parameters' => ['bar' => 'baz'], // extra parameters passed in URI of the request
     *          'files' => [ // The files
     *              ...
     *          ],
     *          'server' => [ // The server parameters (HTTP headers are referenced with a HTTP_ prefix as PHP does)
     *              ...
     *          ]
     *      ]
     *  )
     * </code>
     *
     * @see \Oro\Bundle\TestFrameworkBundle\Test\Client::request
     *
     * @param array $parameters
     */
    protected function restRequest(array $parameters)
    {
        // Assert parameters are expected
        $this->assertArrayHasKey('method', $parameters, 'Failed asserting request method is specified.');
        $parameters['method'] = strtoupper($parameters['method']);
        $this->assertContains(
            $parameters['method'],
            ['POST', 'GET', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'],
            'Failed asserting request method is expected.'
        );

        $defaultParameters = [
            'routeParameters' => [],
            'parameters' => [],
            'files' => [],
            'server' => [],
            'content' => null,
        ];

        // Apply default parameters
        $parameters = array_merge($defaultParameters, $parameters);

        if (!isset($parameters['url'])) {
            $this->assertArrayHasKey('route', $parameters, 'Failed asserting request route is specified.');
            $parameters['url'] = $this->getUrl($parameters['route'], $parameters['routeParameters']);
        }

        $this->client->request(
            $parameters['method'],
            $parameters['url'],
            $parameters['parameters'],
            $parameters['files'],
            $parameters['server'],
            $parameters['content']
        );
    }

    /**
     * Makes request to REST API resource and verifies the response is expected.
     *
     * Example:
     * <code>
     *  $this->sendRestApiRequest(
     *      [
     *          'statusCode' => 200, // Expected status code of the response
     *          'contentType' => 'application/json', // Expected content type of the response
     *      ]
     *  )
     * </code>
     *
     * @param array $parameters
     * @return array|string
     */
    protected function getRestResponseContent(array $parameters)
    {
        // Assert parameters are expected
        $this->assertArrayHasKey('statusCode', $parameters, 'Failed asserting response status code is specified.');

        $defaultParameters = [
            'contentType' => null,
        ];

        // Apply default parameters
        $parameters = array_merge($defaultParameters, $parameters);

        $this->assertResponseStatusCodeEquals(
            $this->client->getResponse(),
            $parameters['statusCode'],
            sprintf(
                'Failed asserting %s %s has expected status code in response.',
                $this->client->getRequest()->getMethod(),
                $this->client->getRequest()->getRequestUri()
            )
        );

        if (!empty($parameters['contentType'])) {
            $this->assertResponseContentTypeEquals(
                $this->client->getResponse(),
                $parameters['contentType'],
                sprintf(
                    'Failed asserting %s %s has expected content type in response.',
                    $this->client->getRequest()->getMethod(),
                    $this->client->getRequest()->getRequestUri()
                )
            );
        }

        $responseContent = $this->client->getResponse()->getContent();

        if ($parameters['contentType'] == 'application/json') {
            $responseContent = $this->jsonToArray($this->client->getResponse()->getContent());
        }

        return $responseContent;
    }

    /**
     * Asserts response is expected. Uses strict compare by default. Disabling strict compare will compare only
     * intersection of expected response with actual response.
     *
     * @param array $expectedResponse
     * @param array $actualResponse
     * @param bool $strictCompare
     */
    protected function assertResponseEquals(array $expectedResponse, array $actualResponse, $strictCompare = true)
    {
        $message = sprintf(
            'Failed asserting %s %s has expected content in response.',
            $this->client->getRequest()->getMethod(),
            $this->client->getRequest()->getRequestUri()
        );

        $this->sortArrayByKeyRecursively($expectedResponse);
        $this->sortArrayByKeyRecursively($actualResponse);

        if ($strictCompare) {
            $this->assertEquals(
                $expectedResponse,
                $actualResponse,
                $message
            );
        } else {
            $this->assertArrayIntersectEquals(
                $expectedResponse,
                $actualResponse,
                $message
            );
        }
    }

    /**
     * Get instance of Doctrine's entity repository.
     *
     * @param string $entityName
     * @return EntityRepository
     */
    protected function getEntityRepository($entityName)
    {
        $result = $this->getDoctrine()->getRepository($entityName);

        $this->assertInstanceOf(EntityRepository::class, $result);

        return $result;
    }

    /**
     * Get instance of Doctrine's manager registry.
     *
     * @return ManagerRegistry
     */
    protected function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }

    /**
     * Get instance of Doctrine's entity manager.
     *
     * @param string $name
     * @return EntityManager
     */
    protected function getEntityManager($name = null)
    {
        $result = $this->getDoctrine()->getManager($name);

        $this->assertInstanceOf(EntityManager::class, $result);

        return $result;
    }

    /**
     * Reloads the same entity from the persistence
     *
     * @param mixed $entity
     * @return mixed
     */
    protected function reloadEntity($entity)
    {
        $id = $this->getIdentifierValues($entity);
        return $this->getEntity(get_class($entity), $id);
    }

    /**
     * Get entity from the persistence
     *
     * @param string $className
     * @param mixed $id
     * @param boolean $optional
     * @return mixed
     */
    protected function getEntity($className, $id, $optional = false)
    {
        $className = ClassUtils::getRealClass($className);

        if (is_array($id) && count($id) == 1) {
            $id = current($id);
        }

        $result = $this->getEntityRepository($className)->find($id);

        if ($result && !$optional) {
            $this->assertInstanceOf(
                $className,
                $result,
                sprintf(
                    'Failed asserting entity "%s" is existing in the persistence.',
                    $className
                )
            );
        }

        return $result;
    }

    /**
     * Refresh all references in the context to pull updates from the persistence.
     */
    public function refreshReferences()
    {
        $referenceRepository = $this->getReferenceRepository();

        foreach ($referenceRepository->getReferences() as $name => $entity) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->getDoctrine()->getManager();
            $contains = $entityManager->contains($entity);

            if ($contains) {
                $entityManager->refresh($entity);
            } else {
                $referenceRepository->setReference(
                    $name,
                    $this->reloadEntity($entity)
                );
            }
        }
    }

    /**
     * @param mixed $entity
     * @return array
     */
    protected function getIdentifierValues($entity)
    {
        $className = ClassUtils::getClass($entity);
        $classMetadata = $this->getEntityManager()->getClassMetadata($className);
        return $classMetadata->getIdentifierValues($entity);
    }

    /**
     * Sorts array by key recursively. This method is used to output failures of array response comparison in
     * a more comprehensive way.
     *
     * @param array $array
     * @return mixed
     */
    protected static function sortArrayByKeyRecursively(array &$array)
    {
        ksort($array);

        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                self::sortArrayByKeyRecursively($value);
            }
        }
    }

    /**
     * Assert response status code equals
     *
     * @param Response    $response
     * @param int         $statusCode
     * @param string|null $message
     */
    public static function assertResponseStatusCodeEquals(Response $response, $statusCode, $message = null)
    {
        try {
            \PHPUnit_Framework_TestCase::assertEquals($statusCode, $response->getStatusCode(), $message);
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            if ($statusCode < 400
                && $response->getStatusCode() >= 400
                && $response->headers->contains('Content-Type', 'application/json')
            ) {
                $content = self::jsonToArray($response->getContent());
                if (!empty($content['message'])) {
                    $errors = null;
                    if (!empty($content['errors'])) {
                        $errors = is_array($content['errors'])
                            ? json_encode($content['errors'])
                            : $content['errors'];
                    }
                    $e = new \PHPUnit_Framework_ExpectationFailedException(
                        $e->getMessage()
                        . ' Error message: ' . $content['message']
                        . ($errors ? '. Errors: ' . $errors : ''),
                        $e->getComparisonFailure()
                    );
                } else {
                    $e = new \PHPUnit_Framework_ExpectationFailedException(
                        $e->getMessage() . ' Response content: ' . $response->getContent(),
                        $e->getComparisonFailure()
                    );
                }
            }
            throw $e;
        }
    }
}
