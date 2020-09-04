<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\TypeValidationLoaderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class TypeValidationLoaderPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var TypeValidationLoaderPass */
    private $compilerPass;

    protected function setUp(): void
    {
        $this->compilerPass = new TypeValidationLoaderPass();
    }

    public function testProcess(): void
    {
        $definition = $this->createMock(Definition::class);
        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with('addLoader', [new Reference('oro_importexport.validator.type_validation_loader')]);

        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with('validator.builder')
            ->willReturn($definition);

        $this->compilerPass->process($containerBuilder);
    }
}
