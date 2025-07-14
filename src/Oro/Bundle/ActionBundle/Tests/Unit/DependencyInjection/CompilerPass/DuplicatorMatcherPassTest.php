<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\DuplicatorMatcherPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DuplicatorMatcherPassTest extends TestCase
{
    private const FACTORY_SERVICE_ID = 'oro_action.factory.duplicator_matcher_factory';
    private const TAG_NAME = 'oro_action.duplicate.matcher_type';

    private DuplicatorMatcherPass $compiler;

    #[\Override]
    protected function setUp(): void
    {
        $this->compiler = new DuplicatorMatcherPass();
    }

    public function testProcessWithoutFactoryService(): void
    {
        $this->compiler->process(new ContainerBuilder());
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $container->register(self::FACTORY_SERVICE_ID)
            ->setArguments([null, []]);
        $container->register('matcher_service_1')
            ->addTag(self::TAG_NAME);
        $container->register('matcher_service_2')
            ->addTag(self::TAG_NAME);

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addObjectType', [new Reference('matcher_service_1')]],
                ['addObjectType', [new Reference('matcher_service_2')]]
            ],
            $container->getDefinition(self::FACTORY_SERVICE_ID)->getMethodCalls()
        );
    }
}
