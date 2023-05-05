<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Reader;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\EntityConfigBundle\Provider\ExportQueryProvider;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\ExportPreGetIds;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\ORM\Query\ExportBufferedIdentityQueryResultIterator;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityReaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var OwnershipMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $ownershipMetadataProvider;

    /** @var ExportQueryProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $exportQueryProvider;

    /** @var EntityReaderTestAdapter */
    private $reader;

    protected function setUp(): void
    {
        $this->contextRegistry = $this->createMock(ContextRegistry::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $this->exportQueryProvider = $this->createMock(ExportQueryProvider::class);

        $this->reader = new EntityReaderTestAdapter(
            $this->contextRegistry,
            $this->doctrine,
            $this->ownershipMetadataProvider,
            $this->exportQueryProvider
        );
    }

    private function getMockStepExecution($context): StepExecution
    {
        $stepExecution = $this->createMock(StepExecution::class);

        $this->contextRegistry->expects(self::any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        return $stepExecution;
    }

    public function testReadMockIterator()
    {
        $iterator = $this->createMock(\Iterator::class);
        $this->doctrine->expects(self::never())
            ->method(self::anything());

        $fooEntity = $this->createMock(\stdClass::class);
        $barEntity = $this->createMock(\ArrayObject::class);
        $bazEntity = $this->createMock(\ArrayAccess::class);

        $iterator->expects(self::once())
            ->method('rewind');
        $iterator->expects(self::exactly(5))
            ->method('valid')
            ->willReturnOnConsecutiveCalls(true, true, true, false, false);
        $iterator->expects(self::exactly(3))
            ->method('current')
            ->willReturnOnConsecutiveCalls($fooEntity, $barEntity, $bazEntity);
        $iterator->expects(self::exactly(3))
            ->method('next');

        $this->reader->setSomeSourceIterator($iterator);

        $context = $this->createMock(ContextInterface::class);
        $context->expects(self::exactly(3))
            ->method('incrementReadOffset');
        $context->expects(self::exactly(3))
            ->method('incrementReadCount');

        $this->reader->setStepExecution($this->getMockStepExecution($context));

        self::assertEquals($fooEntity, $this->reader->read());
        self::assertEquals($barEntity, $this->reader->read());
        self::assertEquals($bazEntity, $this->reader->read());
        self::assertNull($this->reader->read());
        self::assertNull($this->reader->read());
    }

    public function testReadRealIterator()
    {
        $this->doctrine->expects(self::never())
            ->method(self::anything());

        $fooEntity = $this->createMock(\stdClass::class);
        $barEntity = $this->createMock(\ArrayObject::class);
        $bazEntity = $this->createMock(\ArrayAccess::class);

        $iterator = new \ArrayIterator([$fooEntity, $barEntity, $bazEntity]);

        $this->reader->setSomeSourceIterator($iterator);

        $context = $this->createMock(ContextInterface::class);
        $context->expects(self::exactly(3))
            ->method('incrementReadOffset');
        $context->expects(self::exactly(3))
            ->method('incrementReadCount');

        $this->reader->setStepExecution($this->getMockStepExecution($context));

        self::assertEquals($fooEntity, $this->reader->read());
        self::assertEquals($barEntity, $this->reader->read());
        self::assertEquals($bazEntity, $this->reader->read());
        self::assertNull($this->reader->read());
        self::assertNull($this->reader->read());
    }

    public function testReadFailsWhenNoSourceIterator()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Reader must be configured with source');

        $this->doctrine->expects(self::never())
            ->method(self::anything());

        $this->reader->read();
    }

    public function testSetStepExecutionWithQueryBuilder()
    {
        $this->doctrine->expects(self::never())
            ->method(self::anything());

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $context = $this->createMock(ContextInterface::class);
        $context->expects(self::exactly(2))
            ->method('hasOption')
            ->willReturnMap([
                ['entityName', false],
                ['queryBuilder', true]
            ]);
        $context->expects(self::once())
            ->method('getOption')
            ->with('queryBuilder')
            ->willReturn($queryBuilder);

        $this->reader->setStepExecution($this->getMockStepExecution($context));

        self::assertInstanceOf(ExportBufferedIdentityQueryResultIterator::class, $this->reader->getSourceIterator());
        self::assertEquals($queryBuilder, $this->reader->getSourceIterator()->getSource());
    }

    public function testSetStepExecutionWithQuery()
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->expects(self::once())
            ->method('getDefaultQueryHints')
            ->willReturn([]);
        $configuration->expects(self::once())
            ->method('isSecondLevelCacheEnabled')
            ->willReturn(false);

        $em = $this->createMock(EntityManager::class);
        $em->expects(self::exactly(2))
            ->method('getConfiguration')
            ->willReturn($configuration);

        $this->doctrine->expects(self::never())
            ->method(self::anything());

        $query = new Query($em);

        $context = $this->createMock(ContextInterface::class);
        $context->expects(self::exactly(3))
            ->method('hasOption')
            ->willReturnMap([
                ['entityName', false],
                ['queryBuilder', false],
                ['query', true]
            ]);
        $context->expects(self::once())
            ->method('getOption')
            ->with('query')
            ->willReturn($query);

        $this->reader->setStepExecution($this->getMockStepExecution($context));

        self::assertInstanceOf(ExportBufferedIdentityQueryResultIterator::class, $this->reader->getSourceIterator());
        self::assertEquals($query, $this->reader->getSourceIterator()->getSource());
    }

    public function testSetStepExecutionWithEntityName()
    {
        $entityName = 'entityName';

        $classMetadata = $this->createMock(ClassMetadata::class);

        $classMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([]);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $emConfiguration = $this->createMock(Configuration::class);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);
        $entityManager->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($emConfiguration);

        $query = new Query($entityManager);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::any())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($queryBuilder);

        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with($entityName)
            ->willReturn($repository);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityName)
            ->willReturn($entityManager);

        $context = $this->createMock(ContextInterface::class);
        $context->expects(self::once())
            ->method('hasOption')
            ->with('entityName')
            ->willReturn(true);
        $context->expects(self::exactly(3))
            ->method('getOption')
            ->willReturnMap([
                ['entityName', null, $entityName],
                ['ids', [], []]
            ]);

        $this->reader->setStepExecution($this->getMockStepExecution($context));

        self::assertInstanceOf(ExportBufferedIdentityQueryResultIterator::class, $this->reader->getSourceIterator());
        self::assertEquals($query, $this->reader->getSourceIterator()->getSource());
    }

    public function testSetStepExecutionFailsWhenHasNoRequiredOptions()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Configuration of entity reader must contain either "entityName", "queryBuilder" or "query".'
        );

        $this->doctrine->expects(self::never())
            ->method(self::anything());

        $context = $this->createMock(ContextInterface::class);
        $context->expects(self::exactly(3))
            ->method('hasOption')
            ->willReturn(false);

        $this->reader->setStepExecution($this->getMockStepExecution($context));
    }

    public function testSetSourceEntityName()
    {
        $name = \stdClass::class;

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([
                'testSingle'   => ['fieldName' => 'testSingle'],
                'testMultiple' => ['fieldName' => 'testMultiple'],
            ]);

        $this->exportQueryProvider->expects($this->exactly(2))
            ->method('isAssociationExportable')
            ->willReturnMap([
                [$classMetadata, 'testSingle', true],
                [$classMetadata, 'testMultiple', false],
            ]);

        $classMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $queryBuilder->expects(self::once())
            ->method('addSelect')
            ->with('_testSingle');
        $queryBuilder->expects(self::once())
            ->method('leftJoin')
            ->with('o.testSingle', '_testSingle');
        $queryBuilder->expects(self::once())
            ->method('orderBy')
            ->with('o.id', 'ASC');

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($queryBuilder);

        $emConfiguration = $this->createMock(Configuration::class);

        $entityManager = $this->createMock(EntityManager::class);

        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with($name)
            ->willReturn($repository);
        $entityManager->expects(self::once())
            ->method('getClassMetadata')
            ->with($name)
            ->willReturn($classMetadata);
        $entityManager->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($emConfiguration);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($name)
            ->willReturn($entityManager);

        $query = new Query($entityManager);

        $organization = new Organization();
        $ownershipMetadata = new OwnershipMetadata('', '', '', 'organization');
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->willReturn($ownershipMetadata);
        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with('o.organization = :organization')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('organization', $organization)
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::any())
            ->method('getQuery')
            ->willReturn($query);

        $this->reader->setSourceEntityName($name, $organization);
    }

    public function testSetNullIterator()
    {
        $iterator = $this->createMock(\Iterator::class);
        $this->reader->setSourceIterator($iterator);
        self::assertSame($iterator, $this->reader->getSourceIterator());
        $this->reader->setSourceIterator();
        self::assertNull($this->reader->getSourceIterator());
    }

    public function testGetIds()
    {
        $entityName = 'entityName';
        $options = ['entityName' => $entityName];
        $result = [1, 2, 3];

        $classMetadata = $this->createMock(ClassMetadata::class);

        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $classMetadata->expects(self::once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $emConfiguration = $this->createMock(Configuration::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);
        $entityManager->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($emConfiguration);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willReturn([
                1 => 'a',
                2 => 'b',
                3 => 'c',
            ]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::exactly(2))
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('o ', 'o.id')
            ->willReturn($queryBuilder);

        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with($entityName)
            ->willReturn($repository);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityName)
            ->willReturn($entityManager);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(new ExportPreGetIds($queryBuilder, $options), Events::BEFORE_EXPORT_GET_IDS);

        $this->reader->setDispatcher($dispatcher);

        self::assertEquals($result, $this->reader->getIds($entityName, $options));
    }

    public function testGetIdsCompositeKey()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Not supported entity (entityName) with composite primary key.');

        $entityName = 'entityName';
        $options = [];

        $classMetadata = $this->createMock(ClassMetadata::class);

        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id', 'name']);
        $classMetadata->expects(self::never())
            ->method('getSingleIdentifierFieldName');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);
        $entityManager->expects(self::never())
            ->method('getConfiguration');

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::never())
            ->method('getResult');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::never())
            ->method('getQuery');

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::never())
            ->method('createQueryBuilder');

        $entityManager->expects(self::never())
            ->method('getRepository');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityName)
            ->willReturn($entityManager);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::never())
            ->method('dispatch');

        $this->reader->setDispatcher($dispatcher);

        $this->reader->getIds($entityName, $options);
    }
}
