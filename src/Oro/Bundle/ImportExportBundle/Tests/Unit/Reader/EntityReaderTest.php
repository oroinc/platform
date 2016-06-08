<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Reader;

use Doctrine\ORM\Query;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

class EntityReaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $managerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $ownershipMetadataProvider;

    /**
     * @var EntityReaderTestAdapter
     */
    protected $reader;

    protected function setUp()
    {
        $this->contextRegistry = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextRegistry')
            ->disableOriginalConstructor()
            ->setMethods(array('getByStepExecution'))
            ->getMock();

        $this->ownershipMetadataProvider =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->reader = new EntityReaderTestAdapter(
            $this->contextRegistry,
            $this->managerRegistry,
            $this->ownershipMetadataProvider
        );
    }

    public function testReadMockIterator()
    {
        $iterator = $this->getMock('\Iterator');
        $this->managerRegistry->expects($this->never())->method($this->anything());

        $fooEntity = $this->getMock('FooEntity');
        $barEntity = $this->getMock('BarEntity');
        $bazEntity = $this->getMock('BazEntity');

        $iterator->expects($this->at(0))->method('rewind');

        $iterator->expects($this->at(1))->method('valid')->will($this->returnValue(true));
        $iterator->expects($this->at(2))->method('current')->will($this->returnValue($fooEntity));
        $iterator->expects($this->at(3))->method('next');

        $iterator->expects($this->at(4))->method('valid')->will($this->returnValue(true));
        $iterator->expects($this->at(5))->method('current')->will($this->returnValue($barEntity));
        $iterator->expects($this->at(6))->method('next');

        $iterator->expects($this->at(7))->method('valid')->will($this->returnValue(true));
        $iterator->expects($this->at(8))->method('current')->will($this->returnValue($bazEntity));
        $iterator->expects($this->at(9))->method('next');

        $iterator->expects($this->at(10))->method('valid')->will($this->returnValue(false));
        $iterator->expects($this->at(11))->method('valid')->will($this->returnValue(false));

        $this->reader->setSomeSourceIterator($iterator);

        $context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextInterface')->getMock();
        $context->expects($this->exactly(3))->method('incrementReadOffset');
        $context->expects($this->exactly(3))->method('incrementReadCount');

        $stepExecution = $this->getMockStepExecution($context);
        $this->reader->setStepExecution($stepExecution);

        $this->assertEquals($fooEntity, $this->reader->read());
        $this->assertEquals($barEntity, $this->reader->read());
        $this->assertEquals($bazEntity, $this->reader->read());
        $this->assertNull($this->reader->read());
        $this->assertNull($this->reader->read());
    }

    public function testReadRealIterator()
    {
        $this->managerRegistry->expects($this->never())->method($this->anything());

        $fooEntity = $this->getMock('FooEntity');
        $barEntity = $this->getMock('BarEntity');
        $bazEntity = $this->getMock('BazEntity');

        $iterator = new \ArrayIterator(array($fooEntity, $barEntity, $bazEntity));

        $this->reader->setSomeSourceIterator($iterator);

        $context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextInterface')->getMock();
        $context->expects($this->exactly(3))->method('incrementReadOffset');
        $context->expects($this->exactly(3))->method('incrementReadCount');

        $stepExecution = $this->getMockStepExecution($context);
        $this->reader->setStepExecution($stepExecution);

        $this->assertEquals($fooEntity, $this->reader->read());
        $this->assertEquals($barEntity, $this->reader->read());
        $this->assertEquals($bazEntity, $this->reader->read());
        $this->assertNull($this->reader->read());
        $this->assertNull($this->reader->read());
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\LogicException
     * @expectedExceptionMessage Reader must be configured with source
     */
    public function testReadFailsWhenNoSourceIterator()
    {
        $this->managerRegistry->expects($this->never())->method($this->anything());

        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $stepExecution->expects($this->never())->method($this->anything());
        $this->reader->read($stepExecution);
    }

    public function testSetStepExecutionWithQueryBuilder()
    {
        $this->managerRegistry->expects($this->never())->method($this->anything());

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextInterface')->getMock();
        $context->expects($this->at(0))->method('hasOption')->with('entityName')->will($this->returnValue(false));
        $context->expects($this->at(1))->method('hasOption')->with('queryBuilder')->will($this->returnValue(true));
        $context->expects($this->at(2))->method('getOption')
            ->with('queryBuilder')
            ->will($this->returnValue($queryBuilder));

        $this->reader->setStepExecution($this->getMockStepExecution($context));

        $this->assertAttributeInstanceOf(
            'Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator',
            'sourceIterator',
            $this->reader
        );

        $this->assertAttributeEquals(
            $queryBuilder,
            'source',
            self::readAttribute($this->reader, 'sourceIterator')
        );
    }

    public function testSetStepExecutionWithQuery()
    {
        $configuration = $this->getMockBuilder('Doctrine\ORM\Configuration')
            ->disableOriginalConstructor()
            ->getMock();
        $configuration->expects($this->once())
            ->method('getDefaultQueryHints')
            ->will($this->returnValue([]));
        $configuration->expects($this->once())
            ->method('isSecondLevelCacheEnabled')
            ->will($this->returnValue(false));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(2))
            ->method('getConfiguration')
            ->will($this->returnValue($configuration));

        $this->managerRegistry->expects($this->never())->method($this->anything());

        $query = new Query($em);

        $context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextInterface')->getMock();
        $context->expects($this->at(0))->method('hasOption')->with('entityName')->will($this->returnValue(false));
        $context->expects($this->at(1))->method('hasOption')->with('queryBuilder')->will($this->returnValue(false));
        $context->expects($this->at(2))->method('hasOption')->with('query')->will($this->returnValue(true));
        $context->expects($this->at(3))->method('getOption')
            ->with('query')
            ->will($this->returnValue($query));

        $this->reader->setStepExecution($this->getMockStepExecution($context));

        $this->assertAttributeInstanceOf(
            'Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator',
            'sourceIterator',
            $this->reader
        );

        $this->assertAttributeEquals(
            $query,
            'source',
            self::readAttribute($this->reader, 'sourceIterator')
        );
    }

    public function testSetStepExecutionWithEntityName()
    {
        $entityName = 'entityName';

        $classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')->disableOriginalConstructor()
            ->getMock();

        $classMetadata->expects($this->once())->method('getAssociationMappings')->will($this->returnValue(array()));
        $classMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(['id']));

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $entityManager->expects($this->once())->method('getClassMetadata')
            ->with($entityName)
            ->will($this->returnValue($classMetadata));

        $configuration = $this->getMockBuilder('Doctrine\ORM\Configuration')
            ->disableOriginalConstructor()
            ->getMock();
        $configuration->expects($this->once())
            ->method('getDefaultQueryHints')
            ->will($this->returnValue([]));
        $configuration->expects($this->once())
            ->method('isSecondLevelCacheEnabled')
            ->will($this->returnValue(false));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(2))
            ->method('getConfiguration')
            ->will($this->returnValue($configuration));
        $query = new Query($em);
        $queryBuilder = $this
            ->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setMethods(['getQuery'])
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('createQueryBuilder')
            ->with('o')
            ->will($this->returnValue($queryBuilder));

        $entityManager->expects($this->once())->method('getRepository')
            ->with($entityName)
            ->will($this->returnValue($repository));

        $this->managerRegistry->expects($this->once())->method('getManagerForClass')
            ->with($entityName)
            ->will($this->returnValue($entityManager));

        $context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextInterface')->getMock();
        $context->expects($this->at(0))->method('hasOption')->with('entityName')->will($this->returnValue(true));
        $context->expects($this->at(1))->method('getOption')
            ->with('entityName')
            ->will($this->returnValue($entityName));

        $this->reader->setStepExecution($this->getMockStepExecution($context));

        $this->assertAttributeInstanceOf(
            'Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator',
            'sourceIterator',
            $this->reader
        );

        $this->assertAttributeEquals(
            $query,
            'source',
            self::readAttribute($this->reader, 'sourceIterator')
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Configuration of entity reader must contain either "entityName", "queryBuilder" or "query".
     */
    // @codingStandardsIgnoreEnd
    public function testSetStepExecutionFailsWhenHasNoRequiredOptions()
    {
        $this->managerRegistry->expects($this->never())->method($this->anything());

        $context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextInterface')->getMock();
        $context->expects($this->exactly(3))->method('hasOption')->will($this->returnValue(false));

        $this->reader->setStepExecution($this->getMockStepExecution($context));
    }

    public function testSetSourceEntityName()
    {
        $name = '\stdClass';
        $configuration = $this->getMockBuilder('Doctrine\ORM\Configuration')
            ->disableOriginalConstructor()
            ->getMock();
        $configuration->expects($this->once())
            ->method('getDefaultQueryHints')
            ->will($this->returnValue([]));
        $configuration->expects($this->once())
            ->method('isSecondLevelCacheEnabled')
            ->will($this->returnValue(false));
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(2))
            ->method('getConfiguration')
            ->will($this->returnValue($configuration));
        $query = new Query($em);
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(
                ['getQuery', 'getRootAliases', 'setParameter', 'addSelect', 'leftJoin', 'orderBy', 'andWhere']
            )
            ->getMock();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $queryBuilder->expects($this->any())
            ->method('getRootAliases')
            ->will($this->returnValue(['root']));

        $classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->setMethods(
                ['getAssociationMappings', 'isAssociationWithSingleJoinColumn', 'getIdentifierFieldNames']
            )
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->will(
                $this->returnValue(
                    array(
                        'testSingle'   => array('fieldName' => 'testSingle'),
                        'testMultiple' => array('fieldName' => 'testMultiple'),
                    )
                )
            );
        $classMetadata->expects($this->exactly(2))
            ->method('isAssociationWithSingleJoinColumn')
            ->with($this->isType('string'))
            ->will(
                $this->returnValueMap(
                    array(
                        array('testSingle', true),
                        array('testMultiple', false),
                    )
                )
            );
        $classMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(['id']));

        $queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('_testSingle');
        $queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with('o.testSingle', '_testSingle');
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('o.id', 'ASC');

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->will($this->returnValue($queryBuilder));

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with($name)
            ->will($this->returnValue($repository));

        $entityManager->expects($this->once())->method('getClassMetadata')
            ->with($name)
            ->will($this->returnValue($classMetadata));

        $this->managerRegistry->expects($this->once())->method('getManagerForClass')
            ->with($name)
            ->will($this->returnValue($entityManager));

        $organization = new Organization();
        $ownershipMetadata = new OwnershipMetadata('', '', '', 'organization');
        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->will($this->returnValue($ownershipMetadata));
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('o.organization = :organization')
            ->will($this->returnValue($queryBuilder));
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('organization', $organization)
            ->will($this->returnValue($queryBuilder));

        $this->reader->setSourceEntityName($name, $organization);
    }

    /**
     * @param mixed $context
     * @return \PHPUnit_Framework_MockObject_MockObject+
     */
    protected function getMockStepExecution($context)
    {
        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->will($this->returnValue($context));

        return $stepExecution;
    }
}
