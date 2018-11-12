<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ImportExportConfigurationRegistryCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ImportExportConfigurationRegistryCompilerPassTest extends TestCase
{
    /**
     * @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $container;

    /**
     * @var ImportExportConfigurationRegistryCompilerPass
     */
    private $compilerPass;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerBuilder::class);

        $this->compilerPass = new ImportExportConfigurationRegistryCompilerPass();
    }

    public function testProcessWithoutRegistryService()
    {
        $this->container->expects(static::once())
            ->method('has')
            ->willReturn(false);

        $this->container->expects(static::never())
            ->method('findDefinition');

        $this->compilerPass->process($this->container);
    }

    public function testProcess()
    {
        $services = [
            'id1' => [
                ['alias' => 'alias1', 'name' => 'name1'],
                ['alias' => 'alias11', 'name' => 'name11'],
            ],
            'id2' => [
                ['alias' => 'alias2', 'name' => 'name2'],
            ],
        ];

        $definition = $this->createMock(Definition::class);
        $definition->expects(static::exactly(3))
            ->method('addMethodCall')
            ->withConsecutive(
                [
                    'addConfiguration',
                    [
                        new Reference('id1'),
                        'alias1'
                    ]
                ],
                [
                    'addConfiguration',
                    [
                        new Reference('id1'),
                        'alias11'
                    ]
                ],
                [
                    'addConfiguration',
                    [
                        new Reference('id2'),
                        'alias2'
                    ]
                ]
            );

        $this->container->expects(static::once())
            ->method('has')
            ->willReturn(true);

        $this->container->expects(static::once())
            ->method('findDefinition')
            ->willReturn($definition);

        $this->container->expects(static::once())
            ->method('findTaggedServiceIds')
            ->willReturn($services);

        $this->compilerPass->process($this->container);
    }
}
