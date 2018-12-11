<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\ScopeConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ScopeConfigurationPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ScopeConfigurationPass */
    protected $systemConfigurationPass;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->systemConfigurationPass = new ScopeConfigurationPass();
    }

    public function testProcess()
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container **/
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(ScopeConfigurationPass::SCOPE_MANAGER_TAG_NAME)
            ->willReturn([
                'first_scope_service'  => [['scope' => 'user']],
                'second_scope_service' => [['scope' => 'organization']],
                'third_scope_service' => [['scope' => 'website']],
            ]);

        $systemConfigSubscriber = $this->createMock(Definition::class);
        $container
            ->expects($this->once())
            ->method('getDefinition')
            ->with(ScopeConfigurationPass::LOCALIZATION_CHANGE_LISTENER_SERVICE_ID)
            ->willReturn($systemConfigSubscriber);

        $systemConfigSubscriber
            ->expects($this->exactly(3))
            ->method('addMethodCall')
            ->withConsecutive(
                ['addConfigManager', ['user', new Reference('oro_config.user')]],
                ['addConfigManager', ['organization', new Reference('oro_config.organization')]],
                ['addConfigManager', ['website', new Reference('oro_config.website')]]
            );

        $this->systemConfigurationPass->process($container);
    }
}
