<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Component\EntitySerializer\ConfigNormalizer;
use Oro\Component\EntitySerializer\DataNormalizer;
use Oro\Component\EntitySerializer\DoctrineHelper;
use Oro\Component\EntitySerializer\EntityDataAccessor;
use Oro\Component\EntitySerializer\EntityDataTransformer;
use Oro\Component\EntitySerializer\EntitySerializer;
use Oro\Component\EntitySerializer\FieldAccessor;
use Oro\Component\EntitySerializer\QueryFactory;
use Oro\Component\EntitySerializer\ValueTransformer;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;

abstract class EntitySerializerTestCase extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityFieldFilter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
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

        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
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

        $this->entityFieldFilter = $this->getMock('Oro\Component\EntitySerializer\EntityFieldFilterInterface');
        $this->entityFieldFilter->expects($this->any())
            ->method('isApplicableField')
            ->willReturn(true);

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $queryHintResolver = $this->getMock('Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface');

        $doctrineHelper   = new DoctrineHelper($doctrine);
        $dataAccessor     = new EntityDataAccessor();
        $fieldAccessor    = new FieldAccessor($doctrineHelper, $dataAccessor, $this->entityFieldFilter);
        $this->serializer = new EntitySerializer(
            $doctrineHelper,
            $dataAccessor,
            new EntityDataTransformer($this->container, new ValueTransformer()),
            new QueryFactory($doctrineHelper, $queryHintResolver),
            $fieldAccessor,
            new ConfigNormalizer(),
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

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $conn
     * @param string                                   $sql
     * @param array                                    $result
     * @param array                                    $params
     * @param array                                    $types
     */
    protected function setQueryExpectation(
        \PHPUnit_Framework_MockObject_MockObject $conn,
        $sql,
        $result,
        $params = [],
        $types = []
    ) {
        $stmt = $this->createFetchStatementMock($result, $params, $types);
        if ($params) {
            $conn->expects($this->once())
                ->method('prepare')
                ->with($sql)
                ->will($this->returnValue($stmt));
        } else {
            $conn
                ->expects($this->once())
                ->method('query')
                ->with($sql)
                ->will($this->returnValue($stmt));
        }
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $conn
     * @param int                                      $expectsAt
     * @param string                                   $sql
     * @param array                                    $result
     * @param array                                    $params
     * @param array                                    $types
     */
    protected function setQueryExpectationAt(
        \PHPUnit_Framework_MockObject_MockObject $conn,
        $expectsAt,
        $sql,
        $result,
        $params = [],
        $types = []
    ) {
        $stmt = $this->createFetchStatementMock($result, $params, $types);
        if ($params) {
            $conn->expects($this->at($expectsAt))
                ->method('prepare')
                ->with($sql)
                ->will($this->returnValue($stmt));
        } else {
            $conn
                ->expects($this->at($expectsAt))
                ->method('query')
                ->with($sql)
                ->will($this->returnValue($stmt));
        }
    }
}
