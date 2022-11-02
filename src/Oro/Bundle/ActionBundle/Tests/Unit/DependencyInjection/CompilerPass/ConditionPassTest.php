<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ConditionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ConditionPassTest extends \PHPUnit\Framework\TestCase
{
    private const EXTENSION_SERVICE_ID = 'oro_action.expression.extension';
    private const EXPRESSION_TAG = 'oro_action.condition';

    /** @var ConditionPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ConditionPass();
    }

    private function assertExtensionServiceHasValidConfiguration(Definition $definition)
    {
        self::assertFalse($definition->isShared());
        self::assertFalse($definition->isPublic());
    }

    public function testProcessWithoutExtensionService()
    {
        $this->compiler->process(new ContainerBuilder());
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container->register(self::EXTENSION_SERVICE_ID)
            ->setArguments([null, []]);
        $container->register('extension_service_1')
            ->addTag(self::EXPRESSION_TAG, ['alias' => 'service_first|service_first_alias']);
        $container->register('extension_service_2')
            ->addTag(self::EXPRESSION_TAG)
            ->setShared(false)
            ->setPublic(false);

        $this->compiler->process($container);

        self::assertEquals(
            [
                'service_first'       => 'extension_service_1',
                'service_first_alias' => 'extension_service_1',
                'extension_service_2' => 'extension_service_2'
            ],
            $container->getDefinition(self::EXTENSION_SERVICE_ID)->getArgument(1)
        );
        $this->assertExtensionServiceHasValidConfiguration($container->getDefinition('extension_service_1'));
        $this->assertExtensionServiceHasValidConfiguration($container->getDefinition('extension_service_2'));
    }
}
