<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\UserBundle\Entity\Manager\RoleManager;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Entity\Role;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RoleManagerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private RoleManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->manager = new RoleManager($this->doctrine);
    }

    public function testGetUserQueryBuilder(): void
    {
        $role = $this->createMock(Role::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $repository = $this->createMock(RoleRepository::class);
        $repository->expects(self::once())
            ->method('getUserQueryBuilder')
            ->with(self::identicalTo($role))
            ->willReturn($queryBuilder);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        self::assertSame($queryBuilder, $this->manager->getUserQueryBuilder($role));
    }
}
