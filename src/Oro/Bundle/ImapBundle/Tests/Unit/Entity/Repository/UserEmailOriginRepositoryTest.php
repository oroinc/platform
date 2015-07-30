<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Entity\Repository;

use Doctrine\ORM\Query;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class UserEmailOriginRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder('Oro\Bundle\ImapBundle\Entity\Repository\UserEmailOriginRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('createQueryBuilder'))
            ->getMock();
    }

    public function testFindUserEmailOrigin()
    {
        $user = new User();
        $organization = new Organization();

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('andWhere', 'setParameter', 'getQuery', 'getOneOrNullResult'))
            ->getMock();

        $queryBuilder->expects($this->exactly(2))->method('andWhere')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->exactly(2))->method('setParameter')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('getQuery')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('getOneOrNullResult')
            ->will($this->returnSelf());

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        $this->repository->findUserEmailOrigin($user, $organization);
    }
}
