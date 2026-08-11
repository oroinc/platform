<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataAuditBundle\Datagrid\EntityTypeProvider;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Provider\ConfigAuditLevelProvider;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class EntityTypeProviderTest extends TestCase
{
    private EntityClassNameProviderInterface&MockObject $entityClassNameProvider;
    private AuditConfigProvider&MockObject $configProvider;
    private FeatureChecker&MockObject $featureChecker;
    private TranslatorInterface&MockObject $translator;
    private EntityTypeProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityClassNameProvider = $this->createMock(EntityClassNameProviderInterface::class);
        $this->configProvider = $this->createMock(AuditConfigProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(fn (string $key): string => sprintf('[%s]', $key));

        $this->provider = $this->getProvider(
            ['customer', 'customer_group', 'website', 'user', 'organization', 'global']
        );
    }

    /**
     * @param string[] $scopes the configuration scopes the application has
     */
    private function getProvider(array $scopes): EntityTypeProvider
    {
        return new EntityTypeProvider(
            $this->entityClassNameProvider,
            $this->configProvider,
            $this->featureChecker,
            $this->translator,
            new ConfigAuditLevelProvider(array_fill_keys($scopes, null))
        );
    }

    public function testGetEntityTypeForKnownConfigLevel(): void
    {
        $callback = $this->provider->getEntityType();

        self::assertSame(
            '[oro.dataaudit.config.type.user]',
            $callback(new ResultRecord(['objectClass' => 'Oro\Bundle\ConfigBundle\UserConfiguration']))
        );
    }

    public function testGetEntityTypeForUnknownConfigScopeUsesGenericLabel(): void
    {
        $callback = $this->provider->getEntityType();

        self::assertSame(
            'Configuration: My Custom Portal',
            $callback(new ResultRecord(['objectClass' => 'Oro\Bundle\ConfigBundle\MyCustomPortalConfiguration']))
        );
    }

    public function testGetEntityTypeForRegularEntity(): void
    {
        $this->entityClassNameProvider->expects(self::once())
            ->method('getEntityClassName')
            ->with('Oro\Bundle\UserBundle\Entity\User')
            ->willReturn('User');

        $callback = $this->provider->getEntityType();

        self::assertSame(
            'User',
            $callback(new ResultRecord(['objectClass' => 'Oro\Bundle\UserBundle\Entity\User']))
        );
    }

    public function testGetEntityTypesMergesConfigLevelsAndSortsByLabel(): void
    {
        $this->givenAuditableEntities();

        $result = $this->provider->getEntityTypes();

        // 6 configuration levels + the single enabled auditable entity; the disabled one is excluded.
        self::assertCount(7, $result);
        self::assertArrayHasKey('Zebra Entity', $result);
        self::assertContains('Oro\Bundle\ConfigBundle\SystemConfiguration', $result);

        // Ordered by the visible label (array key), case-insensitively.
        $keys = array_keys($result);
        $sortedKeys = $keys;
        sort($sortedKeys, SORT_STRING | SORT_FLAG_CASE);
        self::assertSame($sortedKeys, $keys);
    }

    public function testGetEntityTypesListsOnlyTheLevelsTheApplicationHas(): void
    {
        $this->givenAuditableEntities();

        // A CRM-only application: the commerce configuration scopes do not exist.
        $result = $this->getProvider(['user', 'organization', 'global'])->getEntityTypes();

        self::assertSame(
            [
                '[oro.dataaudit.config.type.organization]',
                '[oro.dataaudit.config.type.system]',
                '[oro.dataaudit.config.type.user]',
                'Zebra Entity',
            ],
            array_keys($result)
        );
    }

    private function givenAuditableEntities(): void
    {
        $this->configProvider->expects(self::once())
            ->method('getAllAuditableEntities')
            ->willReturn(['Some\Entity\Zebra', 'Some\Entity\Disabled']);
        $this->featureChecker->expects(self::any())
            ->method('isResourceEnabled')
            ->willReturnCallback(fn (string $class): bool => 'Some\Entity\Disabled' !== $class);
        $this->entityClassNameProvider->expects(self::any())
            ->method('getEntityClassName')
            ->willReturn('Zebra Entity');
    }
}
