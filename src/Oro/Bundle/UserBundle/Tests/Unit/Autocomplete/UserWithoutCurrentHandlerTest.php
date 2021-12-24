<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Autocomplete;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProviderInterface;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Autocomplete\UserWithoutCurrentHandler;

class UserWithoutCurrentHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const NAME = 'OroUserBundle:User';

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var Indexer|\PHPUnit\Framework\MockObject\MockObject */
    private $indexer;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var UserWithoutCurrentHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->indexer = $this->createMock(Indexer::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $pictureSourcesProvider = $this->createMock(PictureSourcesProviderInterface::class);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->any())
            ->method('getMetadataFor')
            ->with(self::NAME)
            ->willReturn($metadata);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(self::NAME)
            ->willReturn($this->repository);
        $manager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $searchMappingProvider = $this->createMock(SearchMappingProvider::class);
        $searchMappingProvider->expects($this->once())
            ->method('getEntityAlias')
            ->with(self::NAME)
            ->willReturn('user');

        $this->handler = new UserWithoutCurrentHandler(
            $this->tokenAccessor,
            $pictureSourcesProvider,
            self::NAME,
            []
        );
        $this->handler->initSearchIndexer($this->indexer, $searchMappingProvider);
        $this->handler->initDoctrinePropertiesByEntityManager($manager);
        $this->handler->setAclHelper($this->aclHelper);
    }

    /**
     * @dataProvider searchIdsDataProvider
     */
    public function testSearchIds(int $pageSize, int $currentUserId, array $foundUsers, array $expectedUsers)
    {
        $string = 'qwerty';
        $page = 0;

        $foundSearchRecords = [];
        foreach ($foundUsers as $userId) {
            $foundSearchRecords[] = new Item(self::NAME, $userId);
        }

        $this->tokenAccessor->expects($this->any())
            ->method('getUserId')
            ->willReturn($currentUserId);

        $expr = $this->createMock(Expr::class);
        $expr->expects($this->once())
            ->method('in')
            ->with($this->isType('string'), ':entityIds');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('expr')
            ->willReturn($expr);
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('entityIds', $expectedUsers);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($query)
            ->willReturn($query);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $result = new Result(new Query(), $foundSearchRecords);

        $this->indexer->expects($this->once())
            ->method('simpleSearch')
            ->with($string, $page, $pageSize + 2)
            ->willReturn($result);

        $this->handler->search($string, $page, $pageSize);
    }

    public function searchIdsDataProvider(): array
    {
        return [
            'with current user' => [
                'pageSize'      => 5,
                'currentUserId' => 1,
                'foundUsers'    => [1, 2, 3, 4, 5, 6, 7],
                'expectedUsers' => [2, 3, 4, 5, 6, 7],
            ],
            'without current user' => [
                'pageSize'      => 5,
                'currentUserId' => 10,
                'foundUsers'    => [1, 2, 3, 4, 5, 6, 7],
                'expectedUsers' => [1, 2, 3, 4, 5, 6],
            ],
        ];
    }
}
