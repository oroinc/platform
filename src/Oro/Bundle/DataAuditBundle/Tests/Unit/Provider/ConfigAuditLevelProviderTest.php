<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Provider;

use Oro\Bundle\DataAuditBundle\Provider\ConfigAuditLevelProvider;
use PHPUnit\Framework\TestCase;

class ConfigAuditLevelProviderTest extends TestCase
{
    private ConfigAuditLevelProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        // The configuration scopes of a commerce enterprise application, each with the entity its scope id
        // refers to, as the bundles declare it.
        $this->provider = new ConfigAuditLevelProvider([
            'customer' => 'Oro\Bundle\CustomerBundle\Entity\Customer',
            'customer_group' => 'Oro\Bundle\CustomerBundle\Entity\CustomerGroup',
            'website' => 'Oro\Bundle\WebsiteBundle\Entity\Website',
            'user' => 'Oro\Bundle\UserBundle\Entity\User',
            'organization' => 'Oro\Bundle\OrganizationBundle\Entity\Organization',
            'global' => null,
        ]);
    }

    /**
     * @dataProvider getClassForScopeDataProvider
     */
    public function testGetClassForScope(string $scope, string $expectedClass): void
    {
        self::assertSame($expectedClass, $this->provider->getClassForScope($scope));
    }

    public function getClassForScopeDataProvider(): array
    {
        return [
            'the global scope is named system' => ['global', 'Oro\Bundle\ConfigBundle\SystemConfiguration'],
            'single-word scope' => ['user', 'Oro\Bundle\ConfigBundle\UserConfiguration'],
            'multi-word scope' => ['customer_group', 'Oro\Bundle\ConfigBundle\CustomerGroupConfiguration'],
            'a scope of a package the platform knows nothing about' => [
                'my_custom_portal',
                'Oro\Bundle\ConfigBundle\MyCustomPortalConfiguration',
            ],
        ];
    }

    /**
     * @dataProvider isConfigTypeDataProvider
     */
    public function testIsConfigType(?string $objectClass, bool $expected): void
    {
        self::assertSame($expected, $this->provider->isConfigType($objectClass));
    }

    public function isConfigTypeDataProvider(): array
    {
        return [
            'level of this application' => ['Oro\Bundle\ConfigBundle\WebsiteConfiguration', true],
            'level of a package that is gone' => ['Oro\Bundle\ConfigBundle\MyPortalConfiguration', true],
            'a real entity is not a configuration level' => ['Oro\Bundle\UserBundle\Entity\User', false],
            'a config class not matching the convention' => ['Oro\Bundle\ConfigBundle\Entity\Config', false],
            'null' => [null, false],
        ];
    }

    public function testGetLabelKey(): void
    {
        self::assertSame(
            'oro.dataaudit.config.type.system',
            $this->provider->getLabelKey('Oro\Bundle\ConfigBundle\SystemConfiguration')
        );
        self::assertSame(
            'oro.dataaudit.config.type.customer_group',
            $this->provider->getLabelKey('Oro\Bundle\ConfigBundle\CustomerGroupConfiguration')
        );
        // A level this application does not have is named generically instead.
        self::assertNull($this->provider->getLabelKey('Oro\Bundle\ConfigBundle\MyPortalConfiguration'));
    }

    /**
     * @dataProvider getGenericLabelDataProvider
     */
    public function testGetGenericLabel(string $objectClass, string $expected): void
    {
        self::assertSame($expected, $this->provider->getGenericLabel($objectClass));
    }

    public function getGenericLabelDataProvider(): array
    {
        return [
            'multi-word level' => [
                'Oro\Bundle\ConfigBundle\MyCustomPortalConfiguration',
                'Configuration: My Custom Portal',
            ],
            'single-word level' => ['Oro\Bundle\ConfigBundle\SystemConfiguration', 'Configuration: System'],
        ];
    }

    /**
     * @dataProvider getTreeForClassDataProvider
     */
    public function testGetTreeForClass(string $objectClass, string $expected): void
    {
        self::assertSame($expected, $this->provider->getTreeForClass($objectClass));
    }

    public function getTreeForClassDataProvider(): array
    {
        return [
            'system' => ['Oro\Bundle\ConfigBundle\SystemConfiguration', 'system_configuration'],
            'user' => ['Oro\Bundle\ConfigBundle\UserConfiguration', 'user_configuration'],
            'customer group' => [
                'Oro\Bundle\ConfigBundle\CustomerGroupConfiguration',
                'customer_group_configuration',
            ],
            'a level this application does not have falls back to the system tree' => [
                'Oro\Bundle\ConfigBundle\MyPortalConfiguration',
                'system_configuration',
            ],
        ];
    }

    /**
     * @dataProvider getTargetEntityForScopeDataProvider
     */
    public function testGetTargetEntityForScope(string $scope, ?string $expected): void
    {
        self::assertSame($expected, $this->provider->getTargetEntityForScope($scope));
    }

    public function getTargetEntityForScopeDataProvider(): array
    {
        return [
            'the global scope has no entity' => ['global', null],
            'user' => ['user', 'Oro\Bundle\UserBundle\Entity\User'],
            'website' => ['website', 'Oro\Bundle\WebsiteBundle\Entity\Website'],
            'a scope this application does not have' => ['my_custom_portal', null],
        ];
    }

    public function testAllListsEveryLevelOfTheApplication(): void
    {
        $all = $this->provider->all();

        self::assertCount(6, $all);
        self::assertSame('oro.dataaudit.config.type.system', $all['Oro\Bundle\ConfigBundle\SystemConfiguration']);
        self::assertSame('oro.dataaudit.config.type.website', $all['Oro\Bundle\ConfigBundle\WebsiteConfiguration']);
    }

    public function testApplicationWithFewerScopes(): void
    {
        // A CRM installation has neither the commerce scopes nor their levels.
        $provider = new ConfigAuditLevelProvider(['user' => 'Oro\Bundle\UserBundle\Entity\User', 'global' => null]);

        self::assertSame(
            [
                'Oro\Bundle\ConfigBundle\UserConfiguration' => 'oro.dataaudit.config.type.user',
                'Oro\Bundle\ConfigBundle\SystemConfiguration' => 'oro.dataaudit.config.type.system',
            ],
            $provider->all()
        );
        self::assertNull($provider->getTargetEntityForScope('website'));
    }
}
