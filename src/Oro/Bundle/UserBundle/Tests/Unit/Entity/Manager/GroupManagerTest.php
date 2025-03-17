<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Manager\GroupManager;
use Oro\Bundle\UserBundle\Entity\Repository\GroupRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GroupManagerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private GroupManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->manager = new GroupManager($this->doctrine);
    }

    public function testGetUserQueryBuilder(): void
    {
        $group = $this->createMock(Group::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $repository = $this->createMock(GroupRepository::class);
        $repository->expects(self::once())
            ->method('getUserQueryBuilder')
            ->with(self::identicalTo($group))
            ->willReturn($queryBuilder);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        self::assertSame($queryBuilder, $this->manager->getUserQueryBuilder($group));
    }
}
