<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\ExtendDuplicatorPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExtendDuplicatorPassTest extends TestCase
{
    public function testProcessWithoutFactoryService(): void
    {
        // Must not throw when the factory service is absent
        (new ExtendDuplicatorPass())->process(new ContainerBuilder());
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $container->register('oro_action.factory.duplicator_factory');

        (new ExtendDuplicatorPass())->process($container);

        self::assertSame(
            [
                ['addRule', [['extend_storage'], ['propertyName', ['extendEntityStorage']]]],
                ['addRule', [['keep'], ['propertyBagObject', []]]],
            ],
            $container->getDefinition('oro_action.factory.duplicator_factory')->getMethodCalls()
        );
    }
}
