<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EmbeddedFormBundle\DependencyInjection\Compiler\EmbeddedFormPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EmbeddedFormPassTest extends TestCase
{
    private EmbeddedFormPass $compiler;

    #[\Override]
    protected function setUp(): void
    {
        $this->compiler = new EmbeddedFormPass();
    }

    public function testShouldDoNothingWhenThereIsNoManagerDefinition(): void
    {
        $container = new ContainerBuilder();
        $this->compiler->process($container);
    }

    public function testShouldDoNothingWhenThereAreNoTags(): void
    {
        $container = new ContainerBuilder();
        $container->register('oro_embedded_form.manager');

        $this->compiler->process($container);
        self::assertSame([], $container->getDefinition('oro_embedded_form.manager')->getMethodCalls());
    }

    public function testShouldAddTaggedFormTypes(): void
    {
        $container = new ContainerBuilder();
        $container->register('oro_embedded_form.manager');
        $container->register('service_1')->addTag('oro_embedded_form', ['type' => 'type', 'label' => 'label']);
        $container->register('service_2')->addTag('oro_embedded_form', ['type' => 'type_2']);
        $container->register('service_3')->addTag('oro_embedded_form');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addFormType', ['type', 'label']],
                ['addFormType', ['type_2', 'type_2']],
                ['addFormType', ['service_3', 'service_3']],
            ],
            $container->getDefinition('oro_embedded_form.manager')->getMethodCalls()
        );
    }
}
