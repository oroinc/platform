<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProviderInterface;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\UsersUsageStatsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UsersUsageStatsProviderTest extends TestCase
{
    private ObjectManager&MockObject $objectManager;
    private UserRepository&MockObject $userRepository;
    private OrganizationRestrictionProviderInterface&MockObject $organizationRestrictionProvider;
    private UsersUsageStatsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->organizationRestrictionProvider = $this->createMock(OrganizationRestrictionProviderInterface::class);

        $this->provider = new UsersUsageStatsProvider(
            $this->objectManager,
            $this->organizationRestrictionProvider
        );
    }

    public function testIsApplicable(): void
    {
        self::assertTrue($this->provider->isApplicable());
    }

    public function testGetTitle(): void
    {
        self::assertEquals(
            'oro.user.usage_stats.users.label',
            $this->provider->getTitle()
        );
    }

    public function testGetTooltip(): void
    {
        self::assertNull($this->provider->getTooltip());
    }

    public function testGetValue(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $this->objectManager->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        $this->userRepository->expects(self::once())
            ->method('getUsersCountQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn(26);

        $this->organizationRestrictionProvider->expects(self::once())
            ->method('applyOrganizationRestrictions')
            ->with($queryBuilder);

        self::assertSame(
            '26',
            $this->provider->getValue()
        );
    }
}
