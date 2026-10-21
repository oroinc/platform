<?php

declare(strict_types=1);

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Provider\SystemConfigSettingDefinitionProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class SystemConfigSettingDefinitionProviderTest extends TestCase
{
    private ConfigBag&MockObject $configBag;
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private FeatureChecker&MockObject $featureChecker;

    private SystemConfigSettingDefinitionProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configBag = $this->createMock(ConfigBag::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->provider = new SystemConfigSettingDefinitionProvider(
            $this->configBag,
            $this->authorizationChecker,
            $this->featureChecker
        );
    }

    public function testGetDefinitionReturnsDefinitionOfSetting(): void
    {
        $definition = [
            'data_type' => 'integer',
            'type' => IntegerType::class,
            'acl_resource' => 'oro_config_system',
            'options' => ['label' => 'oro.shoppinglist.system_configuration.groups.shopping_list_limit.title'],
        ];

        $this->configBag->expects(self::once())
            ->method('getFieldsRoot')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn($definition);

        self::assertSame($definition, $this->provider->getDefinition('oro_shopping_list.shopping_list_limit'));
    }

    public function testGetDefinitionThrowsExceptionForUnknownSetting(): void
    {
        $this->configBag->expects(self::once())
            ->method('getFieldsRoot')
            ->with('oro_shopping_list.unknown_setting')
            ->willReturn(false);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The system configuration setting "oro_shopping_list.unknown_setting" does not exist.'
        );

        $this->provider->getDefinition('oro_shopping_list.unknown_setting');
    }

    public function testIsAvailableThrowsExceptionForUnknownSetting(): void
    {
        $this->configBag->expects(self::once())
            ->method('getFieldsRoot')
            ->with('oro_shopping_list.unknown_setting')
            ->willReturn(false);
        $this->featureChecker->expects(self::never())
            ->method('isResourceEnabled');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The system configuration setting "oro_shopping_list.unknown_setting" does not exist.'
        );

        $this->provider->isAvailable('oro_shopping_list.unknown_setting');
    }

    public function testIsAvailableReturnsFalseForSettingDisabledByFeature(): void
    {
        $this->configBag->expects(self::once())
            ->method('getFieldsRoot')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn([
                'data_type' => 'integer',
                'type' => IntegerType::class,
                'acl_resource' => 'oro_config_system',
                'options' => ['label' => 'oro.shoppinglist.system_configuration.groups.shopping_list_limit.title'],
            ]);
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with('oro_shopping_list.shopping_list_limit', 'configuration')
            ->willReturn(false);
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertFalse($this->provider->isAvailable('oro_shopping_list.shopping_list_limit'));
    }

    public function testIsAvailableReturnsFalseForSettingWithDeniedAclResource(): void
    {
        $this->configBag->expects(self::once())
            ->method('getFieldsRoot')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn([
                'data_type' => 'integer',
                'type' => IntegerType::class,
                'acl_resource' => 'oro_config_system',
                'options' => ['label' => 'oro.shoppinglist.system_configuration.groups.shopping_list_limit.title'],
            ]);
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with('oro_shopping_list.shopping_list_limit', 'configuration')
            ->willReturn(true);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('oro_config_system')
            ->willReturn(false);

        self::assertFalse($this->provider->isAvailable('oro_shopping_list.shopping_list_limit'));
    }

    public function testIsAvailableReturnsTrueForSettingWithGrantedAclResource(): void
    {
        $this->configBag->expects(self::once())
            ->method('getFieldsRoot')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn([
                'data_type' => 'integer',
                'type' => IntegerType::class,
                'acl_resource' => 'oro_config_system',
                'options' => ['label' => 'oro.shoppinglist.system_configuration.groups.shopping_list_limit.title'],
            ]);
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with('oro_shopping_list.shopping_list_limit', 'configuration')
            ->willReturn(true);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('oro_config_system')
            ->willReturn(true);

        self::assertTrue($this->provider->isAvailable('oro_shopping_list.shopping_list_limit'));
    }

    public function testIsAvailableReturnsTrueForSettingWithoutAclResource(): void
    {
        $this->configBag->expects(self::once())
            ->method('getFieldsRoot')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn([
                'data_type' => 'integer',
                'type' => IntegerType::class,
                'options' => ['label' => 'oro.shoppinglist.system_configuration.groups.shopping_list_limit.title'],
            ]);
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with('oro_shopping_list.shopping_list_limit', 'configuration')
            ->willReturn(true);
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertTrue($this->provider->isAvailable('oro_shopping_list.shopping_list_limit'));
    }
}
