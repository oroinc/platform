<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Autocomplete;

use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Autocomplete\UserWithoutCurrentHandler;

class UserWithoutCurrentHandlerTest extends \PHPUnit\Framework\TestCase
{
    const NAME = 'OroUserBundle:User';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $indexer;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $repository;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $manager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $aclHelper;

    /** @var UserWithoutCurrentHandler */
    protected $handler;

    protected function setUp()
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $attachmentManager = $this->createMock(AttachmentManager::class);
        $this->indexer = $this->createMock(Indexer::class);

        $this->repository = $this->createMock(EntityRepository::class);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->any())
            ->method('getMetadataFor')
            ->with(self::NAME)
            ->will($this->returnValue($metadata));

        $this->manager = $this->createMock(ObjectManager::class);
        $this->manager->expects($this->any())
            ->method('getRepository')
            ->with(self::NAME)
            ->will($this->returnValue($this->repository));
        $this->manager->expects($this->any())
            ->method('getMetadataFactory')
            ->will($this->returnValue($metadataFactory));

        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->handler = new UserWithoutCurrentHandler($this->tokenAccessor, $attachmentManager, self::NAME, []);
        $this->handler->initSearchIndexer($this->indexer, [self::NAME => ['alias' => 'user']]);
        $this->handler->initDoctrinePropertiesByEntityManager($this->manager);
        $this->handler->setAclHelper($this->aclHelper);
    }

    /**
     * @param int $pageSize
     * @param int $currentUserId
     * @param array $foundUsers
     * @param array $expectedUsers
     * @dataProvider searchIdsDataProvider
     */
    public function testSearchIds($pageSize, $currentUserId, array $foundUsers, array $expectedUsers)
    {
        $string = 'qwerty';
        $page = 0;

        $foundSearchRecords = [];
        foreach ($foundUsers as $userId) {
            $foundSearchRecords[] = new Item(self::NAME, $userId);
        }

        $this->tokenAccessor->expects($this->any())
            ->method('getUserId')
            ->will($this->returnValue($currentUserId));

        $expr = $this->createMock('Doctrine\ORM\Query\Expr');
        $expr->expects($this->once())
            ->method('in')
            ->with($this->isType('string'), $expectedUsers);

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder->expects($this->once())
            ->method('expr')
            ->will($this->returnValue($expr));

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue([]));

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        $result = new Result(new Query(), $foundSearchRecords);

        $this->indexer->expects($this->once())
            ->method('simpleSearch')
            ->with($string, $page, $pageSize + 2)
            ->will($this->returnValue($result));

        $this->handler->search($string, $page, $pageSize);
    }

    /**
     * @return array
     */
    public function searchIdsDataProvider()
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
