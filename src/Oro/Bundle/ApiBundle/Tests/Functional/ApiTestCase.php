<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Parser;

use Oro\Bundle\ApiBundle\Processor\Shared\EntityTypeFeatureCheck;
use Oro\Bundle\ApiBundle\Request\DataType;
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
        $this->initClient([], $this->getRequestParameters());

        /** @var ContainerInterface $container */
        $container = $this->getContainer();

        $this->valueNormalizer = $container->get('oro_api.value_normalizer');
        $this->doctrineHelper  = $container->get('oro_api.doctrine_helper');
    }

    /**
     * @return array
     */
    protected function getRequestParameters()
    {
        return $this->generateWsseAuthHeader();
    }

    /**
     * @return RequestType
     */
    abstract protected function getRequestType();

    /**
     * @param string $entityClass
     *
     * @return string
     */
    protected function getEntityType($entityClass)
    {
        return $this->valueNormalizer->normalizeValue(
            $entityClass,
            DataType::ENTITY_TYPE,
            $this->getRequestType()
        );
    }

    /**
     * @return array [entity class => [entity class, [excluded action, ...]], ...]
     */
    public function getEnabledEntities()
    {
        $entities = $this->getEntities();
        $enabledEntities = [];
        foreach ($entities as $entityClass => $value) {
            if (!$this->isEntityEnabled($entityClass)) {
                continue;
            }

            $enabledEntities[$entityClass] = $value;
        }
        if (!$enabledEntities) {
            $this->markTestSkipped('There are no enabled entities to test');
        }

        return $enabledEntities;
    }

    /**
     * @return array [entity class => [entity class, [excluded action, ...]], ...]
     */
    public function getDisabledEntities()
    {
        $entities = $this->getEntities();
        $disabledEntities = [];
        foreach ($entities as $entityClass => $value) {
            if ($this->isEntityEnabled($entityClass)) {
                continue;
            }

            $disabledEntities[$entityClass] = $value;
        }
        if (!$disabledEntities) {
            $this->markTestSkipped('There are no disabled entities to test');
        }

        return $disabledEntities;
    }

    /**
     * @return array [entity class => [entity class, [excluded action, ...]], ...]
     */
    public function getEntities()
    {
        $this->initClient();

        $result = [];
        $doctrineHelper = $this->getContainer()->get('oro_api.doctrine_helper');
        $resourcesProvider = $this->getContainer()->get('oro_api.resources_provider');
        $resources = $resourcesProvider->getResources(Version::LATEST, $this->getRequestType());
        foreach ($resources as $resource) {
            $entityClass = $resource->getEntityClass();
            if (!$doctrineHelper->isManageableEntityClass($entityClass)) {
                continue;
            }
            $result[$entityClass] = [$entityClass, $resource->getExcludedActions()];
        }

        return $result;
    }

    /**
     * @return array [[entity class, association name, ApiSubresource], ...]
     */
    public function getSubresources()
    {
        $this->initClient();

        $result = [];
        $resourcesProvider = $this->getContainer()->get('oro_api.resources_provider');
        $subresourcesProvider = $this->getContainer()->get('oro_api.subresources_provider');
        $resources = $resourcesProvider->getResources(Version::LATEST, $this->getRequestType());
        foreach ($resources as $resource) {
            $entityClass = $resource->getEntityClass();
            $subresources = $subresourcesProvider->getSubresources(
                $entityClass,
                Version::LATEST,
                $this->getRequestType()
            );
            if (null !== $subresources) {
                foreach ($subresources->getSubresources() as $associationName => $subresource) {
                    $result[] = [$entityClass, $associationName, $subresource];
                }
            }
        }

        return $result;
    }

    /**
     * @param string $entityClass
     *
     * @return mixed
     */
    protected function findEntityId($entityClass)
    {
        /** @var EntityManager|null $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass($entityClass);
        if (!$em) {
            return null;
        }

        $ids = $em->getRepository($entityClass)->createQueryBuilder('e')
            ->select(
                implode(
                    ',',
                    array_map(
                        function ($fieldName) {
                            return 'e.' . $fieldName;
                        },
                        $em->getClassMetadata($entityClass)->getIdentifierFieldNames()
                    )
                )
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();
        if (empty($ids)) {
            return null;
        }

        $ids = reset($ids);

        return 1 === count($ids)
            ? reset($ids)
            : $ids;
    }

    /**
     * @param mixed $entityId
     *
     * @return string|null
     */
    protected function getRestApiEntityId($entityId)
    {
        if (null === $entityId) {
            return null;
        }

        return $this->getContainer()->get('oro_api.rest.entity_id_transformer')
            ->transform($entityId);
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
     * @param Response  $response
     * @param int|int[] $statusCode
     * @param string    $entityName
     * @param string    $requestType
     */
    protected static function assertApiResponseStatusCodeEquals(
        Response $response,
        $statusCode,
        $entityName,
        $requestType
    ) {
        try {
            static::assertResponseStatusCodeEquals($response, $statusCode);
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            $e = new \PHPUnit_Framework_ExpectationFailedException(
                sprintf(
                    'Expects %s status code for "%s" request for entity: "%s". Error message: %s',
                    is_array($statusCode) ? implode(', ', $statusCode) : $statusCode,
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
     * @param Response   $response
     * @param int|int[]  $statusCode
     * @param string     $entityName
     * @param string     $requestType
     * @param array|null $content
     */
    protected static function assertUpdateApiResponseStatusCodeEquals(
        Response $response,
        $statusCode,
        $entityName,
        $requestType,
        $content
    ) {
        try {
            static::assertResponseStatusCodeEquals($response, $statusCode);
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            $e = new \PHPUnit_Framework_ExpectationFailedException(
                sprintf(
                    'Expects %s status code for "%s" request for entity: "%s". Error message: %s. Content: %s',
                    is_array($statusCode) ? implode(', ', $statusCode) : $statusCode,
                    $requestType,
                    $entityName,
                    $e->getMessage(),
                    is_array($content) ? json_encode($content) : (string)$content
                ),
                $e->getComparisonFailure()
            );
            throw $e;
        }
    }

    /**
     * Assert response status code equals
     *
     * @param Response  $response
     * @param int|int[] $statusCode
     */
    public static function assertResponseStatusCodeEquals(Response $response, $statusCode)
    {
        try {
            if (is_array($statusCode)) {
                if (!in_array($response->getStatusCode(), $statusCode, true)) {
                    throw new \PHPUnit_Framework_ExpectationFailedException(
                        sprintf(
                            'Failed asserting that %s is one of %s',
                            $response->getStatusCode(),
                            implode(', ', $statusCode)
                        )
                    );
                }
            } else {
                \PHPUnit_Framework_TestCase::assertEquals($statusCode, $response->getStatusCode());
            }
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            if ((is_array($statusCode) ? min($statusCode) : $statusCode) <= 400
                && $response->getStatusCode() >= 400
                && (
                    $response->headers->contains('Content-Type', 'application/json')
                    || $response->headers->contains('Content-Type', 'application/vnd.api+json')
                )
            ) {
                $e = new \PHPUnit_Framework_ExpectationFailedException(
                    $e->getMessage() . "\nResponse content: " . $response->getContent(),
                    $e->getComparisonFailure()
                );
            }
            throw $e;
        }
    }

    /**
     * @return bool
     */
    protected function isEntityEnabled($entityClass)
    {
        return $this->getContainer()->get('oro_featuretoggle.checker.feature_checker')
            ->isResourceEnabled($entityClass, EntityTypeFeatureCheck::API_RESOURCE_KEY);
    }
}
