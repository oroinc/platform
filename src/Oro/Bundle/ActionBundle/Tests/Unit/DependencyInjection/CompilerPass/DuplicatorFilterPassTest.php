<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\DuplicatorFilterPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DuplicatorFilterPassTest extends \PHPUnit\Framework\TestCase
{
    private const FACTORY_SERVICE_ID = 'oro_action.factory.duplicator_filter_factory';
    private const TAG_NAME = 'oro_action.duplicate.filter_type';

    /** @var DuplicatorFilterPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new DuplicatorFilterPass();
    }

    public function testProcessWithoutFactoryService()
    {
        $this->compiler->process(new ContainerBuilder());
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container->register(self::FACTORY_SERVICE_ID)
            ->setArguments([null, []]);
        $container->register('filter_service_1')
            ->addTag(self::TAG_NAME);
        $container->register('filter_service_2')
            ->addTag(self::TAG_NAME);

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addObjectType', [new Reference('filter_service_1')]],
                ['addObjectType', [new Reference('filter_service_2')]]
            ],
            $container->getDefinition(self::FACTORY_SERVICE_ID)->getMethodCalls()
        );
    }
}
