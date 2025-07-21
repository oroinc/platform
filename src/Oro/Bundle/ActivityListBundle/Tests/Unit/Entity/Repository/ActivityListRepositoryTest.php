<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActivityListRepositoryTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private ActivityListRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->repository = new ActivityListRepository(
            $this->entityManager,
            new ClassMetadata(ActivityList::class)
        );
    }

    /**
     * @dataProvider paramsProvider
     */
    public function testGetActivityListQueryBuilder(
        string $entityClass,
        int $entityId,
        array $activityClasses,
        ?\DateTime $dateFrom,
        ?\DateTime $dateTo,
        int $andWhereCount,
        int $setParameterCount
    ): void {
        $qb = $this->createMock(QueryBuilder::class);
        $expr = $this->createMock(Expr::class);
        $qb->expects($this->once())
            ->method('select')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('from')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('leftJoin')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('orderBy')
            ->willReturnSelf();
        $qb->expects($this->never())
            ->method('groupBy')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('expr')
            ->willReturn($expr);
        $expr->expects($this->any())
            ->method('in');
        $expr->expects($this->any())
            ->method('between');
        $qb->expects($this->exactly($andWhereCount))
            ->method('andWhere')
            ->willReturnSelf();
        $qb->expects($this->exactly($setParameterCount))
            ->method('setParameter')
            ->willReturnSelf();

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $this->repository->getActivityListQueryBuilder(
            $entityClass,
            $entityId,
            $activityClasses,
            $dateFrom,
            $dateTo
        );
    }

    public function paramsProvider(): array
    {
        $now = new \DateTime();
        $past = clone $now;
        $past = $past->sub(new \DateInterval('P2M'));
        $className = 'Acme\Bundle\AcmeBundle\Entity\Test';
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
