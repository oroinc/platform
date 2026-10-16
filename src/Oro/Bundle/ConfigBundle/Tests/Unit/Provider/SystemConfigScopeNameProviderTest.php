<?php

declare(strict_types=1);

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\AbstractScopeManager;
use Oro\Bundle\ConfigBundle\Provider\SystemConfigScopeNameProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SystemConfigScopeNameProviderTest extends TestCase
{
    private AbstractScopeManager&MockObject $userScopeManager;
    private AbstractScopeManager&MockObject $organizationScopeManager;
    private AbstractScopeManager&MockObject $globalScopeManager;
    private SystemConfigScopeNameProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->userScopeManager = $this->createMock(AbstractScopeManager::class);
        $this->organizationScopeManager = $this->createMock(AbstractScopeManager::class);
        $this->globalScopeManager = $this->createMock(AbstractScopeManager::class);

        $this->provider = new SystemConfigScopeNameProvider();
        $this->provider->addScopeManager('user', $this->userScopeManager);
        $this->provider->addScopeManager('organization', $this->organizationScopeManager);
        $this->provider->addScopeManager('global', $this->globalScopeManager);
    }

    public function testGetScopeNameByEntityReturnsScopeSupportingEntity(): void
    {
        $organization = new Organization();
        ReflectionUtil::setId($organization, 42);

        $this->userScopeManager->expects(self::once())
            ->method('getScopeIdFromEntity')
            ->with(self::identicalTo($organization))
            ->willReturn(null);
        $this->organizationScopeManager->expects(self::once())
            ->method('getScopeIdFromEntity')
            ->with(self::identicalTo($organization))
            ->willReturn(42);
        $this->globalScopeManager->expects(self::never())
            ->method('getScopeIdFromEntity');

        self::assertSame('organization', $this->provider->getScopeNameByEntity($organization));
    }

    public function testGetScopeNameByEntityReturnsHighestPriorityScopeWhenSeveralScopesSupportEntity(): void
    {
        $user = new User();
        ReflectionUtil::setId($user, 7);

        $this->userScopeManager->expects(self::once())
            ->method('getScopeIdFromEntity')
            ->with(self::identicalTo($user))
            ->willReturn(7);
        $this->organizationScopeManager->expects(self::never())
            ->method('getScopeIdFromEntity');
        $this->globalScopeManager->expects(self::never())
            ->method('getScopeIdFromEntity');

        self::assertSame('user', $this->provider->getScopeNameByEntity($user));
    }

    public function testGetScopeNameByEntityThrowsExceptionForUnsavedEntity(): void
    {
        $organization = new Organization();

        $this->userScopeManager->expects(self::once())
            ->method('getScopeIdFromEntity')
            ->with(self::identicalTo($organization))
            ->willReturn(null);
        $this->organizationScopeManager->expects(self::once())
            ->method('getScopeIdFromEntity')
            ->with(self::identicalTo($organization))
            ->willReturn(0);
        $this->globalScopeManager->expects(self::once())
            ->method('getScopeIdFromEntity')
            ->with(self::identicalTo($organization))
            ->willReturn(0);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'There is no system configuration scope for the entity "%s".',
            Organization::class
        ));

        $this->provider->getScopeNameByEntity($organization);
    }

    public function testGetScopeNameByEntityThrowsExceptionWhenNoScopeManagersAdded(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There is no system configuration scope for the entity "stdClass".');

        (new SystemConfigScopeNameProvider())->getScopeNameByEntity(new \stdClass());
    }

    public function testGetScopeNameByEntityReturnsLastAddedScopeForNull(): void
    {
        $this->userScopeManager->expects(self::never())
            ->method('getScopeIdFromEntity');
        $this->organizationScopeManager->expects(self::never())
            ->method('getScopeIdFromEntity');
        $this->globalScopeManager->expects(self::never())
            ->method('getScopeIdFromEntity');

        self::assertSame('global', $this->provider->getScopeNameByEntity(null));
    }

    public function testGetScopeNameByEntityThrowsExceptionForNullWhenNoScopeManagersAdded(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There is no global system configuration scope.');

        (new SystemConfigScopeNameProvider())->getScopeNameByEntity(null);
    }
}
