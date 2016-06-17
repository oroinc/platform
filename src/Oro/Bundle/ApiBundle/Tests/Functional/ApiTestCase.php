<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Parser;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * Local cache for expectations
     *
     * @var array
     */
    private $expectations = [];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        /** @var ContainerInterface $container */
        $container = $this->getContainer();

        $this->valueNormalizer = $container->get('oro_api.value_normalizer');
        $this->doctrineHelper  = $container->get('oro_api.doctrine_helper');
    }

    /**
     * @return RequestType
     */
    abstract protected function getRequestType();

    /**
     * @return array [entity class => [entity class, [excluded action, ...]], ...]
     */
    public function getEntities()
    {
        $this->initClient();
        $entities = [];
        $container = $this->getContainer();
        $resourcesProvider = $container->get('oro_api.resources_provider');
        $resources = $resourcesProvider->getResources(Version::LATEST, $this->getRequestType());
        foreach ($resources as $resource) {
            $entityClass = $resource->getEntityClass();

            $entities[$entityClass] = [$entityClass, $resource->getExcludedActions()];
        }

        return $entities;
    }

    /**
     * @param string $filename
     *
     * @return array
     */
    protected function loadExpectation($filename)
    {
        if (!isset($this->expectations[$filename])) {
            $expectedContent = file_get_contents(
                __DIR__ . DIRECTORY_SEPARATOR . 'Stub' . DIRECTORY_SEPARATOR . $filename
            );

            $ymlParser = new Parser();

            $this->expectations[$filename] = $ymlParser->parse($expectedContent);
        }

        return $this->expectations[$filename];
    }

    /**
     * @param Response $response
     * @param integer  $statusCode
     * @param string   $entityName
     * @param string   $requestType
     */
    protected function assertApiResponseStatusCodeEquals(Response $response, $statusCode, $entityName, $requestType)
    {
        try {
            $this->assertResponseStatusCodeEquals($response, $statusCode);
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            $e = new \PHPUnit_Framework_ExpectationFailedException(
                sprintf(
                    'Wrong %s response for "%s" request for entity: "%s". Error message: %s',
                    $statusCode,
                    $requestType,
                    $entityName,
                    $e->getMessage()
                ),
                $e->getComparisonFailure()
            );
            throw $e;
        }
    }

    /**
     * Assert response status code equals
     *
     * @param Response $response
     * @param int      $statusCode
     */
    public static function assertResponseStatusCodeEquals(Response $response, $statusCode)
    {
        try {
            \PHPUnit_Framework_TestCase::assertEquals($statusCode, $response->getStatusCode());
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            if ($statusCode < 400
                && $response->getStatusCode() >= 400
                && (
                    $response->headers->contains('Content-Type', 'application/json')
                    || $response->headers->contains('Content-Type', 'application/vnd.api+json')
                )
            ) {
                $e = new \PHPUnit_Framework_ExpectationFailedException(
                    $e->getMessage() . ' Response content: ' . $response->getContent(),
                    $e->getComparisonFailure()
                );
            }
            throw $e;
        }
    }
}
