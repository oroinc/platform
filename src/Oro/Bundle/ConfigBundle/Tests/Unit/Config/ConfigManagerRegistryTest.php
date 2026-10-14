<?php

declare(strict_types=1);

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManagerRegistry;
use Oro\Bundle\ConfigBundle\Provider\SystemConfigScopeNameProvider;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ConfigManagerRegistryTest extends TestCase
{
    private SystemConfigScopeNameProvider&MockObject $systemConfigScopeNameProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->systemConfigScopeNameProvider = $this->createMock(SystemConfigScopeNameProvider::class);
    }

    public function testGetConfigManagerReturnsAddedConfigManagerOfScope(): void
    {
        $websiteConfigManager = $this->createMock(ConfigManager::class);
        $organizationConfigManager = $this->createMock(ConfigManager::class);

        $registry = new ConfigManagerRegistry($this->systemConfigScopeNameProvider);
        $registry->addConfigManager('website', $websiteConfigManager);
        $registry->addConfigManager('organization', $organizationConfigManager);

        self::assertSame($websiteConfigManager, $registry->getConfigManager('website'));
        self::assertSame($organizationConfigManager, $registry->getConfigManager('organization'));
    }

    public function testGetConfigManagerThrowsExceptionForUnknownScope(): void
    {
        $registry = new ConfigManagerRegistry($this->systemConfigScopeNameProvider);
        $registry->addConfigManager('organization', $this->createMock(ConfigManager::class));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The configuration scope "website" does not exist.');

        $registry->getConfigManager('website');
    }

    public function testGetConfigManagerByEntityReturnsConfigManagerOfScopeOfEntity(): void
    {
        $organization = new Organization();
        ReflectionUtil::setId($organization, 42);
        $organizationConfigManager = $this->createMock(ConfigManager::class);

        $registry = new ConfigManagerRegistry($this->systemConfigScopeNameProvider);
        $registry->addConfigManager('user', $this->createMock(ConfigManager::class));
        $registry->addConfigManager('organization', $organizationConfigManager);
        $registry->addConfigManager('global', $this->createMock(ConfigManager::class));

        $this->systemConfigScopeNameProvider->expects(self::once())
            ->method('getScopeNameByEntity')
            ->with(self::identicalTo($organization))
            ->willReturn('organization');

        self::assertSame($organizationConfigManager, $registry->getConfigManagerByEntity($organization));
    }

    public function testGetConfigManagerByEntityReturnsConfigManagerOfGlobalScopeForNull(): void
    {
        $globalConfigManager = $this->createMock(ConfigManager::class);

        $registry = new ConfigManagerRegistry($this->systemConfigScopeNameProvider);
        $registry->addConfigManager('user', $this->createMock(ConfigManager::class));
        $registry->addConfigManager('organization', $this->createMock(ConfigManager::class));
        $registry->addConfigManager('global', $globalConfigManager);

        $this->systemConfigScopeNameProvider->expects(self::once())
            ->method('getScopeNameByEntity')
            ->with(null)
            ->willReturn('global');

        self::assertSame($globalConfigManager, $registry->getConfigManagerByEntity(null));
    }

    public function testGetConfigManagerByEntityThrowsExceptionForNullWhenNoScopes(): void
    {
        $registry = new ConfigManagerRegistry($this->systemConfigScopeNameProvider);

        $this->systemConfigScopeNameProvider->expects(self::once())
            ->method('getScopeNameByEntity')
            ->with(null)
            ->willThrowException(new \LogicException('There is no global system configuration scope.'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There is no global system configuration scope.');

        $registry->getConfigManagerByEntity(null);
    }

    public function testGetConfigManagerByEntityThrowsExceptionForEntityNotSupportedByAnyScope(): void
    {
        $businessUnit = new BusinessUnit();
        ReflectionUtil::setId($businessUnit, 7);

        $registry = new ConfigManagerRegistry($this->systemConfigScopeNameProvider);
        $registry->addConfigManager('organization', $this->createMock(ConfigManager::class));
        $registry->addConfigManager('global', $this->createMock(ConfigManager::class));

        $this->systemConfigScopeNameProvider->expects(self::once())
            ->method('getScopeNameByEntity')
            ->with(self::identicalTo($businessUnit))
            ->willThrowException(new \LogicException(sprintf(
                'There is no system configuration scope for the entity "%s".',
                BusinessUnit::class
            )));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'There is no system configuration scope for the entity "%s".',
            BusinessUnit::class
        ));

        $registry->getConfigManagerByEntity($businessUnit);
    }

    public function testGetConfigManagerByEntityThrowsExceptionWhenScopeOfEntityHasNoConfigManager(): void
    {
        $organization = new Organization();
        ReflectionUtil::setId($organization, 42);

        $registry = new ConfigManagerRegistry($this->systemConfigScopeNameProvider);
        $registry->addConfigManager('global', $this->createMock(ConfigManager::class));

        $this->systemConfigScopeNameProvider->expects(self::once())
            ->method('getScopeNameByEntity')
            ->with(self::identicalTo($organization))
            ->willReturn('organization');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The configuration scope "organization" does not exist.');

        $registry->getConfigManagerByEntity($organization);
    }

    public function testGetScopesReturnsEmptyArrayWhenNoConfigManagersAdded(): void
    {
        $registry = new ConfigManagerRegistry($this->systemConfigScopeNameProvider);

        self::assertSame([], $registry->getScopes());
    }

    public function testGetScopesReturnsScopesInAddedOrder(): void
    {
        $registry = new ConfigManagerRegistry($this->systemConfigScopeNameProvider);
        $registry->addConfigManager('website', $this->createMock(ConfigManager::class));
        $registry->addConfigManager('organization', $this->createMock(ConfigManager::class));

        self::assertSame(['website', 'organization'], $registry->getScopes());
    }
}
