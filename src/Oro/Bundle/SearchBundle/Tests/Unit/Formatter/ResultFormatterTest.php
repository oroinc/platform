<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Formatter;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Formatter\ResultFormatter;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Tests\Unit\Formatter\Stub\Category;
use Oro\Bundle\SearchBundle\Tests\Unit\Formatter\Stub\Product;

class ResultFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    private array $productStubs;
    private array $categoryStubs;

    private function prepareEntityManager()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityMetadataForClass', 'getSingleEntityIdentifierFieldName', 'getEntityRepository'])
            ->getMock();
    }

    private function prepareStubData(): array
    {
        // create product stubs
        $productEntities = [];
        for ($i = 1; $i <= 5; $i++) {
            $indexerItem = new Item(Product::getEntityName(), $i);
            $entity = new Product($i);
            $productEntities[$i] = $entity;

            $this->productStubs[$i] = [
                'indexer_item' => $indexerItem,
                'entity' => $entity,
            ];
        }

        $productMetadata = new ClassMetadata(Product::getEntityName());
        $productMetadata->setIdentifier(['id']);
        $reflectionProperty = new \ReflectionProperty(
            Product::class,
            'id'
        );
        $reflectionProperty->setAccessible(true);
        $productMetadata->reflFields['id'] = $reflectionProperty;

        // create category stubs
        $categoryEntities = [];
        for ($i = 1; $i <= 3; $i++) {
            $indexerItem = new Item(Category::getEntityName(), $i);
            $entity = new Category($i);
            $categoryEntities[$i] = $entity;

            $this->categoryStubs[$i] = [
                'indexer_item' => $indexerItem,
                'entity' => $entity,
            ];
        }

        $categoryMetadata = new ClassMetadata(Category::getEntityName());
        $categoryMetadata->setIdentifier(['id']);
        $reflectionProperty = new \ReflectionProperty(
            Category::class,
            'id'
        );
        $reflectionProperty->setAccessible(true);
        $categoryMetadata->reflFields['id'] = $reflectionProperty;

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [Product::getEntityName(), true, $productMetadata],
                [Category::getEntityName(), true, $categoryMetadata],
            ]);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->willReturn('id');

        return [
            Product::getEntityName()  => $productEntities,
            Category::getEntityName() => $categoryEntities,
        ];
    }

    private function prepareEntityRepository(array $entities, array $entityIds): EntityRepository
    {
        $query = $this->getMockForAbstractClass(
            AbstractQuery::class,
            [],
            '',
            false,
            true,
            true,
            ['getResult']
        );
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($entities);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->onlyMethods(['where', 'getQuery', 'setParameter'])
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('e.id IN (:entityIds)')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('entityIds', $entityIds)
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->any())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);

        return $repository;
    }

    private function prepareRepositories(array $productEntities, array $categoryEntities)
    {
        $productRepository = $this->prepareEntityRepository(
            $productEntities,
            [1,2,3,4,5]
        );

        $categoryRepository = $this->prepareEntityRepository(
            $categoryEntities,
            [1,2,3]
        );

        // entity manager behaviour
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturnMap([
                [Product::getEntityName(), $productRepository],
                [Category::getEntityName(), $categoryRepository],
            ]);
    }

    private function prepareStubEntities(): array
    {
        $stubEntities = $this->prepareStubData();
        $this->prepareRepositories(
            $stubEntities[Product::getEntityName()],
            $stubEntities[Category::getEntityName()]
        );

        return $stubEntities;
    }

    private function getIndexerRows(): array
    {
        $indexerRows = [];
        foreach ([$this->productStubs, $this->categoryStubs] as $stubElements) {
            foreach ($stubElements as $stubElement) {
                $indexerRows[] = $stubElement['indexer_item'];
            }
        }

        return $indexerRows;
    }

    private function getOrderedEntities(): array
    {
        $entities = [];
        foreach ([$this->productStubs, $this->categoryStubs] as $stubElements) {
            foreach ($stubElements as $stubElement) {
                $entities[] = $stubElement['entity'];
            }
        }

        return $entities;
    }

    public function testGetResultEntities()
    {
        $this->prepareEntityManager();
        $expectedResult = $this->prepareStubEntities();

        $indexerRows = $this->getIndexerRows();

        $resultFormatter = new ResultFormatter($this->doctrineHelper);
        $actualResult = $resultFormatter->getResultEntities($indexerRows);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testGetOrderedResultEntities()
    {
        $this->prepareEntityManager();
        $this->prepareStubEntities();

        $indexerRows = $this->getIndexerRows();

        $resultFormatter = new ResultFormatter($this->doctrineHelper);
        $actualResult = $resultFormatter->getOrderedResultEntities($indexerRows);

        $expectedResult = $this->getOrderedEntities();

        $this->assertEquals($expectedResult, $actualResult);
    }
}
