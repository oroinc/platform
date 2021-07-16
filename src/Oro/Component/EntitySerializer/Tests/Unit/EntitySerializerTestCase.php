<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Oro\Component\EntitySerializer\ConfigConverter;
use Oro\Component\EntitySerializer\ConfigNormalizer;
use Oro\Component\EntitySerializer\DataNormalizer;
use Oro\Component\EntitySerializer\DataTransformer;
use Oro\Component\EntitySerializer\DoctrineHelper;
use Oro\Component\EntitySerializer\EntityDataAccessor;
use Oro\Component\EntitySerializer\EntityFieldFilterInterface;
use Oro\Component\EntitySerializer\EntitySerializer;
use Oro\Component\EntitySerializer\FieldAccessor;
use Oro\Component\EntitySerializer\FieldFilterInterface;
use Oro\Component\EntitySerializer\QueryFactory;
use Oro\Component\EntitySerializer\QueryResolver;
use Oro\Component\EntitySerializer\SerializationHelper;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class EntitySerializerTestCase extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityFieldFilter;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    protected $container;

    /** @var EntitySerializer */
    protected $serializer;

    protected function setUp(): void
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'Test' => 'Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity'
            ]
        );

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);
        $doctrine->expects($this->any())
            ->method('getAliasNamespace')
            ->willReturnMap([
                ['Test', 'Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity']
            ]);

        $this->entityFieldFilter = $this->createMock(EntityFieldFilterInterface::class);
        $this->entityFieldFilter->expects($this->any())
            ->method('isApplicableField')
            ->willReturn(true);

        $queryHintResolver = $this->createMock(QueryHintResolverInterface::class);
        $queryHintResolver->expects($this->any())
            ->method('resolveHints')
            ->willReturnCallback(function (Query $query, array $hints = []) {
                if (!empty($hints)) {
                    foreach ($hints as $hint) {
                        if (is_array($hint)) {
                            $query->setHint($hint['name'], $hint['value']);
                        } elseif (is_string($hint)) {
                            $query->setHint($hint, true);
                        }
                    }
                }
            });

        $this->container = $this->createMock(ContainerInterface::class);
        $doctrineHelper = new DoctrineHelper($doctrine);
        $dataAccessor = new EntityDataAccessor();
        $fieldAccessor = new FieldAccessor($doctrineHelper, $dataAccessor, $this->entityFieldFilter);
        $this->serializer = new EntitySerializer(
            $doctrineHelper,
            new SerializationHelper(new DataTransformer($this->container)),
            $dataAccessor,
            new QueryFactory($doctrineHelper, new QueryResolver($queryHintResolver)),
            $fieldAccessor,
            new ConfigNormalizer(),
            new ConfigConverter(),
            new DataNormalizer()
        );
    }

    /**
     * @param array  $expected
     * @param array  $actual
     * @param string $message
     */
    protected function assertArrayEquals(array $expected, array $actual, $message = '')
    {
        $this->sortByKeyRecursive($expected);
        $this->sortByKeyRecursive($actual);
        $this->assertSame($expected, $actual, $message);
    }

    /**
     * @param string $expected
     * @param string $actual
     * @param string $message
     */
    protected function assertDqlEquals($expected, $actual, $message = '')
    {
        $expected = str_replace('Test:', 'Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\\', $expected);
        $this->assertEquals($expected, $actual, $message);
    }

    protected function sortByKeyRecursive(array &$array)
    {
        ksort($array);
        foreach ($array as &$val) {
            if ($val && is_array($val)) {
                $this->sortByKeyRecursive($val);
            }
        }
    }

    /**
     * @param array $checkRules
     *
     * @return FieldFilterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getFieldFilter(array $checkRules)
    {
        $filter = $this->createMock(FieldFilterInterface::class);
        $filter->expects(self::any())
            ->method('checkField')
            ->willReturnCallback(function ($entity, $entityClass, $field) use ($checkRules) {
                return $checkRules[$field] ?? null;
            });

        return $filter;
    }
}
