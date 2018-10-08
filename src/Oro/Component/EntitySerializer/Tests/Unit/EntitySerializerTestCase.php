<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Oro\Component\EntitySerializer\ConfigConverter;
use Oro\Component\EntitySerializer\ConfigNormalizer;
use Oro\Component\EntitySerializer\DataNormalizer;
use Oro\Component\EntitySerializer\DoctrineHelper;
use Oro\Component\EntitySerializer\EntityDataAccessor;
use Oro\Component\EntitySerializer\EntityDataTransformer;
use Oro\Component\EntitySerializer\EntityFieldFilterInterface;
use Oro\Component\EntitySerializer\EntitySerializer;
use Oro\Component\EntitySerializer\FieldAccessor;
use Oro\Component\EntitySerializer\QueryFactory;
use Oro\Component\EntitySerializer\QueryResolver;
use Oro\Component\EntitySerializer\SerializationHelper;
use Oro\Component\EntitySerializer\ValueTransformer;
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

    protected function setUp()
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
            ->will($this->returnValue($this->em));
        $doctrine->expects($this->any())
            ->method('getAliasNamespace')
            ->will(
                $this->returnValueMap(
                    [
                        ['Test', 'Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity']
                    ]
                )
            );

        $this->entityFieldFilter = $this->createMock(EntityFieldFilterInterface::class);
        $this->entityFieldFilter->expects($this->any())
            ->method('isApplicableField')
            ->willReturn(true);

        $this->container = $this->createMock(ContainerInterface::class);

        $queryHintResolver = $this->createMock(QueryHintResolverInterface::class);

        $doctrineHelper   = new DoctrineHelper($doctrine);
        $dataAccessor     = new EntityDataAccessor();
        $fieldAccessor    = new FieldAccessor($doctrineHelper, $dataAccessor, $this->entityFieldFilter);
        $this->serializer = new EntitySerializer(
            $doctrineHelper,
            new SerializationHelper(
                new EntityDataTransformer($this->container, new ValueTransformer())
            ),
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

    /**
     * @param array $array
     */
    protected function sortByKeyRecursive(array &$array)
    {
        ksort($array);
        foreach ($array as &$val) {
            if ($val && is_array($val)) {
                $this->sortByKeyRecursive($val);
            }
        }
    }
}
