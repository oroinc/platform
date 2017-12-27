<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\NotificationBundle\Entity\Repository\RecipientListRepository;

class RecipientListRepositoryTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_NAME = 'OroUserBundle:User';

    /**
     * @var RecipientListRepository
     */
    protected $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder'])
            ->getMock();

        $this->repository = new RecipientListRepository($this->entityManager, new ClassMetadata(self::ENTITY_NAME));
    }

    protected function tearDown()
    {
        unset($this->repository);
        unset($this->entityManager);
    }

    public function testGetRecipientEmails()
    {
        $userMock = $this->createMock('Oro\Bundle\UserBundle\Entity\User');
        $userMock->expects($this->once())
            ->method('getEmail')
            ->will($this->returnValue('a@a.com'));

        $groupMock = $this->createMock('Oro\Bundle\UserBundle\Entity\Group');
        $groupMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));

        $users = new ArrayCollection([$userMock]);
        $groups = new ArrayCollection([$groupMock]);

        /** @var RecipientList|\PHPUnit_Framework_MockObject_MockObject $recipientList */
        $recipientList = $this->createMock(RecipientList::class);
        $recipientList->expects($this->once())
            ->method('getUsers')
            ->will($this->returnValue($users));
        $recipientList->expects($this->once())
            ->method('getGroups')
            ->will($this->returnValue($groups));

        $recipientList->expects($this->exactly(2))
            ->method('getEmail')
            ->will($this->returnValue('a@a.com'));

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())->method('getResult')
            ->will($this->returnValue([['email' => 'b@b.com']]));

        $entityAlias = 'u';

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['select', 'from', 'getQuery', 'leftJoin', 'where', 'setParameter'])
            ->getMock();
        $queryBuilder->expects($this->once())->method('select')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('from')->with(self::ENTITY_NAME, $entityAlias)
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('getQuery')
            ->will($this->returnValue($query));
        $queryBuilder->expects($this->once())->method('leftJoin')->with('u.groups', 'groups')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('where')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('setParameter')
            ->will($this->returnSelf());

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        $emails = $this->repository->getRecipientEmails($recipientList);
        $this->assertCount(2, $emails);
    }
}
