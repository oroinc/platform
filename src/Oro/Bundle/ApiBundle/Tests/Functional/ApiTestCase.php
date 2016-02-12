<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Parser;

use Oro\Bundle\ApiBundle\Request\JsonApi\EntityClassTransformer;
use Oro\Bundle\ApiBundle\Request\RestRequest;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityClassTransformer */
    protected $entityClassTransformer;

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

        $this->entityClassTransformer = $container->get('oro_api.json_api.entity_class_transformer');
        $this->doctrineHelper         = $container->get('oro_api.doctrine_helper');
    }

    /**
     * @return string[]
     */
    abstract protected function getRequestType();

    /**
     * @return array
     */
    public function getEntities()
    {
        $this->initClient();
        $entities        = [];
        $container       = $this->getContainer();
        $resourcesLoader = $container->get('oro_api.public_resources_loader');
        $resources       = $resourcesLoader->getResources(Version::LATEST, $this->getRequestType());
        foreach ($resources as $resource) {
            $entityClass = $resource->getEntityClass();

            $entities[$entityClass] = [$entityClass];
        }

        return $entities;
    }

    /**
     * @param string $entityClass
     * @param array  $content
     *
     * @return array
     */
    protected function getGetRequestConfig($entityClass, $content)
    {
        $recordExist   = count($content) === 1;
        $recordContent = $recordExist ? $content [0] : [];
        $idFields      = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
        $idFieldCount  = count($idFields);
        if ($idFieldCount === 1) {
            // single identifier
            return [$recordExist ? $recordContent[reset($idFields)] : 1, $recordExist];
        } elseif ($idFieldCount > 1) {
            // combined identifier
            $requirements = [];
            foreach ($idFields as $field) {
                $requirements[$field] = $recordExist ? $content[$field] : 1;
            }

            return [implode(RestRequest::ARRAY_DELIMITER, $requirements), $recordExist];
        }
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
}
