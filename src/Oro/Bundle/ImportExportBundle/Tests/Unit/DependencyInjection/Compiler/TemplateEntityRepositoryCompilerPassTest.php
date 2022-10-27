<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\TemplateEntityRepositoryCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TemplateEntityRepositoryCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var TemplateEntityRepositoryCompilerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new TemplateEntityRepositoryCompilerPass();
    }

    public function testProcessNoTemplateManager()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $templateManagerDef = $container->register('oro_importexport.template_fixture.manager');

        $container->register('fixture_1')
            ->addTag('oro_importexport.template_fixture');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addEntityRepository', [new Reference('fixture_1')]]
            ],
            $templateManagerDef->getMethodCalls()
        );
    }

    public function testProcessWhenNoFixtures()
    {
        $container = new ContainerBuilder();
        $templateManagerDef = $container->register('oro_importexport.template_fixture.manager');

        $this->compiler->process($container);

        self::assertSame([], $templateManagerDef->getMethodCalls());
    }
}
