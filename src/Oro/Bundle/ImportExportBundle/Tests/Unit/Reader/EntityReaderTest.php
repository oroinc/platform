<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Reader;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\EntityConfigBundle\Provider\ExportQueryProvider;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\ExportPreGetIds;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityReaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|MockObject */
    protected $managerRegistry;

    /** @var ContextRegistry|MockObject */
    protected $contextRegistry;

    /** @var OwnershipMetadataProviderInterface|MockObject */
    protected $ownershipMetadataProvider;

    /** @var ExportQueryProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $exportQueryProvider;

    /** @var EntityReaderTestAdapter */
    protected $reader;

    protected function setUp(): void
    {
        $this->contextRegistry = $this->createMock(ContextRegistry::class);
        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->exportQueryProvider = $this->createMock(ExportQueryProvider::class);
        $this->reader = new EntityReaderTestAdapter(
            $this->contextRegistry,
            $this->managerRegistry,
            $this->ownershipMetadataProvider
        );

        $this->reader->setExportQueryProvider($this->exportQueryProvider);
    }

    public function testReadMockIterator()
    {
        $iterator = $this->createMock(\Iterator::class);
        $this->managerRegistry->expects(static::never())->method(static::anything());

        $fooEntity = $this->createMock(\stdClass::class);
        $barEntity = $this->createMock(\ArrayObject::class);
        $bazEntity = $this->createMock(\ArrayAccess::class);

        $iterator->expects(static::at(0))->method('rewind');

        $iterator->expects(static::at(1))->method('valid')->willReturn(true);
        $iterator->expects(static::at(2))->method('current')->willReturn($fooEntity);
        $iterator->expects(static::at(3))->method('next');

        $iterator->expects(static::at(4))->method('valid')->willReturn(true);
        $iterator->expects(static::at(5))->method('current')->willReturn($barEntity);
        $iterator->expects(static::at(6))->method('next');

        $iterator->expects(static::at(7))->method('valid')->willReturn(true);
        $iterator->expects(static::at(8))->method('current')->willReturn($bazEntity);
        $iterator->expects(static::at(9))->method('next');

        $iterator->expects(static::at(10))->method('valid')->willReturn(false);
        $iterator->expects(static::at(11))->method('valid')->willReturn(false);

        $this->reader->setSomeSourceIterator($iterator);

        $context = $this->getMockBuilder(ContextInterface::class)->getMock();
        $context->expects(static::exactly(3))->method('incrementReadOffset');
        $context->expects(static::exactly(3))->method('incrementReadCount');

        $stepExecution = $this->getMockStepExecution($context);
        $this->reader->setStepExecution($stepExecution);

        static::assertEquals($fooEntity, $this->reader->read());
        static::assertEquals($barEntity, $this->reader->read());
        static::assertEquals($bazEntity, $this->reader->read());
        static::assertNull($this->reader->read());
        static::assertNull($this->reader->read());
    }

    public function testReadRealIterator()
    {
        $this->managerRegistry->expects(static::never())->method(static::anything());

        $fooEntity = $this->createMock(\stdClass::class);
        $barEntity = $this->createMock(\ArrayObject::class);
        $bazEntity = $this->createMock(\ArrayAccess::class);

        $iterator = new \ArrayIterator([$fooEntity, $barEntity, $bazEntity]);

        $this->reader->setSomeSourceIterator($iterator);

        $context = $this->getMockBuilder(ContextInterface::class)->getMock();
        $context->expects(static::exactly(3))->method('incrementReadOffset');
        $context->expects(static::exactly(3))->method('incrementReadCount');

        $stepExecution = $this->getMockStepExecution($context);
        $this->reader->setStepExecution($stepExecution);

        static::assertEquals($fooEntity, $this->reader->read());
        static::assertEquals($barEntity, $this->reader->read());
        static::assertEquals($bazEntity, $this->reader->read());
        static::assertNull($this->reader->read());
        static::assertNull($this->reader->read());
    }

    public function testReadFailsWhenNoSourceIterator()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Reader must be configured with source');

        $this->managerRegistry->expects(static::never())->method(static::anything());

        $this->reader->read();
    }

    public function testSetStepExecutionWithQueryBuilder()
    {
        $this->managerRegistry->expects(static::never())->method(static::anything());

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(ContextInterface::class)->getMock();
        $context->expects(static::at(0))->method('hasOption')->with('entityName')->willReturn(false);
        $context->expects(static::at(1))->method('hasOption')->with('queryBuilder')->willReturn(true);
        $context->expects(static::at(2))->method('getOption')->with('queryBuilder')->willReturn($queryBuilder);

        $this->reader->setStepExecution($this->getMockStepExecution($context));

        static::assertInstanceOf(BufferedIdentityQueryResultIterator::class, $this->reader->getSourceIterator());
        static::assertEquals($queryBuilder, $this->reader->getSourceIterator()->getSource());
    }

    public function testSetStepExecutionWithQuery()
    {
        $configuration = $this->getMockBuilder(Configuration::class)->disableOriginalConstructor()->getMock();
        $configuration->expects(static::once())->method('getDefaultQueryHints')->willReturn([]);
        $configuration->expects(static::once())->method('isSecondLevelCacheEnabled')->willReturn(false);

        /** @var EntityManager|MockObject $em */
        $em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $em->expects(static::exactly(2))->method('getConfiguration')->willReturn($configuration);

        $this->managerRegistry->expects(static::never())->method(static::anything());

        $query = new Query($em);

        $context = $this->getMockBuilder(ContextInterface::class)->getMock();
        $context->expects(static::at(0))->method('hasOption')->with('entityName')->willReturn(false);
        $context->expects(static::at(1))->method('hasOption')->with('queryBuilder')->willReturn(false);
        $context->expects(static::at(2))->method('hasOption')->with('query')->willReturn(true);
        $context->expects(static::at(3))->method('getOption')->with('query')->willReturn($query);

        $this->reader->setStepExecution($this->getMockStepExecution($context));

        static::assertInstanceOf(BufferedIdentityQueryResultIterator::class, $this->reader->getSourceIterator());
        static::assertEquals($query, $this->reader->getSourceIterator()->getSource());
    }

    public function testSetStepExecutionWithEntityName()
    {
        $entityName = 'entityName';

        $classMetadata = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();

        $classMetadata->expects(static::once())->method('getAssociationMappings')->willReturn([]);
        $classMetadata->expects(static::once())->method('getIdentifierFieldNames')->willReturn(['id']);

        $emConfiguration = $this->getMockBuilder(Configuration::class)->disableOriginalConstructor()->getMock();

        /** @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $entityManager->expects(static::once())->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);
        $entityManager->expects(static::any())->method('getConfiguration')->willReturn($emConfiguration);

        $query = new Query($entityManager);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $queryBuilder->expects(static::any())->method('getQuery')->willReturn($query);

        $repository = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects(static::once())->method('createQueryBuilder')
            ->with('o')
            ->willReturn($queryBuilder);

        $entityManager->expects(static::once())->method('getRepository')
            ->with($entityName)
            ->willReturn($repository);

        $this->managerRegistry->expects(static::once())->method('getManagerForClass')
            ->with($entityName)
            ->willReturn($entityManager);

        $context = $this->getMockBuilder(ContextInterface::class)->getMock();
        $context->expects(static::at(0))->method('hasOption')->with('entityName')->willReturn(true);
        $context->expects(static::at(1))->method('getOption')->with('entityName')->willReturn($entityName);
        $context->expects(static::at(3))->method('getOption')->with('ids', [])->willReturn([]);

        $this->reader->setStepExecution($this->getMockStepExecution($context));

        static::assertInstanceOf(BufferedIdentityQueryResultIterator::class, $this->reader->getSourceIterator());
        static::assertEquals($query, $this->reader->getSourceIterator()->getSource());
    }

    public function testSetStepExecutionFailsWhenHasNoRequiredOptions()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Configuration of entity reader must contain either "entityName", "queryBuilder" or "query".'
        );

        $this->managerRegistry->expects(static::never())->method(static::anything());

        $context = $this->getMockBuilder(ContextInterface::class)->getMock();
        $context->expects(static::exactly(3))->method('hasOption')->willReturn(false);

        $this->reader->setStepExecution($this->getMockStepExecution($context));
    }

    public function testSetSourceEntityName()
    {
        $name = '\stdClass';

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $classMetadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->expects(static::once())
            ->method('getAssociationMappings')
            ->willReturn([
                'testSingle' => ['fieldName' => 'testSingle'],
                'testMultiple' => ['fieldName' => 'testMultiple'],
            ]);
        $this->exportQueryProvider
            ->expects($this->exactly(2))
            ->method('isAssociationExportable')
            ->willReturnMap([
                [$classMetadata, 'testSingle', true],
                [$classMetadata, 'testMultiple', false],
            ]);

        $classMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $queryBuilder->expects(static::once())->method('addSelect')->with('_testSingle');
        $queryBuilder->expects(static::once())->method('leftJoin')->with('o.testSingle', '_testSingle');
        $queryBuilder->expects(static::once())->method('orderBy')->with('o.id', 'ASC');

        $repository = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects(static::once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($queryBuilder);

        $emConfiguration = $this->getMockBuilder(Configuration::class)->disableOriginalConstructor()->getMock();

        /** @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        $entityManager->expects(static::once())->method('getRepository')->with($name)->willReturn($repository);
        $entityManager->expects(static::once())->method('getClassMetadata')->with($name)->willReturn($classMetadata);
        $entityManager->expects(static::any())->method('getConfiguration')->willReturn($emConfiguration);

        $this->managerRegistry->expects(static::once())
            ->method('getManagerForClass')
            ->with($name)
            ->willReturn($entityManager);

        $query = new Query($entityManager);

        $organization = new Organization();
        $ownershipMetadata = new OwnershipMetadata('', '', '', 'organization');
        $this->ownershipMetadataProvider->expects(static::once())
            ->method('getMetadata')
            ->willReturn($ownershipMetadata);
        $queryBuilder->expects(static::once())
            ->method('andWhere')
            ->with('o.organization = :organization')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(static::once())
            ->method('setParameter')
            ->with('organization', $organization)
            ->willReturn($queryBuilder);
        $queryBuilder->expects(static::any())->method('getQuery')->willReturn($query);

        $this->reader->setSourceEntityName($name, $organization);
    }

    /**
     * @param mixed $context
     *
     * @return MockObject|StepExecution
     */
    protected function getMockStepExecution($context)
    {
        $stepExecution = $this->getMockBuilder(StepExecution::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextRegistry->expects(static::any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        return $stepExecution;
    }

    public function testSetNullIterator()
    {
        $iterator = $this->createMock('\Iterator');
        $this->reader->setSourceIterator($iterator);
        static::assertSame($iterator, $this->reader->getSourceIterator());
        $this->reader->setSourceIterator();
        static::assertNull($this->reader->getSourceIterator());
    }

    public function testGetIds()
    {
        $entityName = 'entityName';
        $options = [];
        $result = [1, 2, 3];

        $classMetadata = $this->createMock(ClassMetadata::class);

        $classMetadata->expects(static::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $classMetadata->expects(static::once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $emConfiguration = $this->createMock(Configuration::class);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(static::exactly(2))
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);
        $entityManager->expects(static::any())
            ->method('getConfiguration')
            ->willReturn($emConfiguration);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(static::once())
            ->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willReturn([
                1 => 'a',
                2 => 'b',
                3 => 'c',
            ]);

        /** @var QueryBuilder|MockObject $queryBuilder */
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(static::exactly(2))
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(static::once())
            ->method('createQueryBuilder')
            ->with('o ', 'o.id')
            ->willReturn($queryBuilder);

        $entityManager->expects(static::once())
            ->method('getRepository')
            ->with($entityName)
            ->willReturn($repository);

        $this->managerRegistry->expects(static::once())
            ->method('getManagerForClass')
            ->with($entityName)
            ->willReturn($entityManager);

        /** @var EventDispatcherInterface|MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(static::once())
            ->method('dispatch')
            ->with(new ExportPreGetIds($queryBuilder, $options), Events::BEFORE_EXPORT_GET_IDS);

        $this->reader->setDispatcher($dispatcher);

        static::assertEquals($result, $this->reader->getIds($entityName, $options));
    }

    public function testGetIdsCompositeKey()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Not supported entity (entityName) with composite primary key.');

        $entityName = 'entityName';
        $options = [];

        $classMetadata = $this->createMock(ClassMetadata::class);

        $classMetadata->expects(static::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id', 'name']);
        $classMetadata->expects(static::never())
            ->method('getSingleIdentifierFieldName');

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(static::once())
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);
        $entityManager->expects(static::never())->method('getConfiguration');

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(static::never())->method('getResult');

        /** @var QueryBuilder|MockObject $queryBuilder */
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(static::never())->method('getQuery');

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(static::never())->method('createQueryBuilder');

        $entityManager->expects(static::never())->method('getRepository');

        $this->managerRegistry->expects(static::once())
            ->method('getManagerForClass')
            ->with($entityName)
            ->willReturn($entityManager);

        /** @var EventDispatcherInterface|MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(static::never())->method('dispatch');

        $this->reader->setDispatcher($dispatcher);

        $this->reader->getIds($entityName, $options);
    }
}
