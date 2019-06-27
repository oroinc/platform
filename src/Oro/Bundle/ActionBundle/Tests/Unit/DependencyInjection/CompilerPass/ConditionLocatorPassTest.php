<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ConditionLocatorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ConditionLocatorPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess(): void
    {
        $condition = new Definition();
        $condition->addTag('oro_action.condition');

        $conditionLocator = new Definition(ServiceLocator::class, [[]]);

        $container = new ContainerBuilder();
        $container->setDefinition('condition-id', $condition);
        $container->setDefinition('oro_action.condition_locator', $conditionLocator);

        $this->assertEquals([], $conditionLocator->getArgument(0));

        $pass = new ConditionLocatorPass();
        $pass->process($container);

        $this->assertEquals(
            [
                'condition-id' => new Reference('condition-id')
            ],
            $conditionLocator->getArgument(0)
        );
    }
}
