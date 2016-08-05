<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Entity\Repository;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;

use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;

class ActivityListRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActivityListRepository */
    protected $repository;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(array('createQueryBuilder'))
            ->getMock();

        $this->repository = new ActivityListRepository(
            $this->entityManager,
            new ClassMetadata('Oro\Bundle\ActivityListBundle\Entity\ActivityList')
        );
    }

    protected function tearDown()
    {
        unset($this->entityManager, $this->repository);
    }

    /**
     * @dataProvider paramsProvider
     *
     * @param string    $entityClass
     * @param integer   $entityId
     * @param array     $activityClasses
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param integer   $andWhereCount Number of andWhere() calls
     * @param integer   $setParameterCount Number of setParameter() calls
     */
    public function testGetActivityListQueryBuilder(
        $entityClass,
        $entityId,
        $activityClasses,
        $dateFrom,
        $dateTo,
        $andWhereCount,
        $setParameterCount
    ) {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $expr = $this->getMock('Doctrine\ORM\Query\Expr');

        $qb->expects($this->once())
            ->method('select')
            ->will($this->returnSelf());

        $qb->expects($this->once())
            ->method('from')
            ->will($this->returnSelf());

        $qb->expects($this->once())
            ->method('where')
            ->will($this->returnSelf());

        $qb->expects($this->exactly(2))
            ->method('leftJoin')
            ->will($this->returnSelf());

        $qb->expects($this->any())
            ->method('orderBy')
            ->will($this->returnSelf());

        $qb->expects($this->never())
            ->method('groupBy')
            ->will($this->returnSelf());

        $qb->expects($this->any())
            ->method('expr')
            ->will($this->returnValue($expr));

        $expr->expects($this->any())
            ->method('in');

        $expr->expects($this->any())
            ->method('between');

        $qb->expects($this->exactly($andWhereCount))
            ->method('andWhere')
            ->will($this->returnSelf());

        $qb->expects($this->exactly($setParameterCount))
            ->method('setParameter')
            ->will($this->returnSelf());

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));

        $this->repository->getActivityListQueryBuilder(
            $entityClass,
            $entityId,
            $activityClasses,
            $dateFrom,
            $dateTo
        );
    }

    /**
     * @return array
     */
    public function paramsProvider()
    {
        $now          = new \DateTime();
        $past         = clone $now;
        $past         = $past->sub(new \DateInterval("P2M"));
        $className    = 'Acme\Bundle\AcmeBundle\Entity\Test';
        $activityName = 'Acme\Bundle\AcmeBundle\Activity\Test';

        return [
            'default'              => [$className, 1, [], null, null, 0, 1],
            'both_date_empty'      => [$className, 1, [$activityName], null, null, 1, 2],
            'dateFrom_empty'       => [$className, 1, [$activityName], null, $now, 1, 2],
            'dateTo_classes_empty' => [$className, 1, [], $past, null, 1, 2],
            'dateTo_empty'         => [$className, 1, [$activityName], $past, null, 2, 3],
            'all_params'           => [$className, 1, [$activityName], $past, $now, 2, 4]
        ];
    }
}
