<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Autocomplete;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\UserBundle\Autocomplete\UserWithoutCurrentHandler;

class UserWithoutCurrentHandlerTest extends \PHPUnit_Framework_TestCase
{
    const NAME = 'OroUserBundle:User';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $aclHelper;

    /**
     * @var UserWithoutCurrentHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $attachmentManager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexer = $this->getMockBuilder('Oro\Bundle\SearchBundle\Engine\Indexer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));

        $metadataFactory = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadataFactory');
        $metadataFactory->expects($this->any())
            ->method('getMetadataFor')
            ->with(self::NAME)
            ->will($this->returnValue($metadata));

        $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->manager->expects($this->any())
            ->method('getRepository')
            ->with(self::NAME)
            ->will($this->returnValue($this->repository));
        $this->manager->expects($this->any())
            ->method('getMetadataFactory')
            ->will($this->returnValue($metadataFactory));

        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new UserWithoutCurrentHandler($this->securityFacade, $attachmentManager, self::NAME, []);
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
            $foundSearchRecords[] = new Item($this->manager, self::NAME, $userId);
        }

        $this->securityFacade->expects($this->any())
            ->method('getLoggedUserId')
            ->will($this->returnValue($currentUserId));

        $expr = $this->getMock('Doctrine\ORM\Query\Expr');
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
