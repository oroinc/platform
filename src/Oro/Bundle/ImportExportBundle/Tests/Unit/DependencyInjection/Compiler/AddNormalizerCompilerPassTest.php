<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\AddNormalizerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AddNormalizerCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var Definition|\PHPUnit\Framework\MockObject\MockObject */
    private $serializerDefinition;

    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $containerBuilder;

    protected function setUp(): void
    {
        $this->serializerDefinition = $this->createMock(Definition::class);
        $this->containerBuilder = $this->createMock(ContainerBuilder::class);
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(
        array $normalizers,
        array $encoders,
        array $expectedNormalizers,
        array $expectedEncoders
    ) {
        $this->containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with('oro_importexport.serializer')
            ->willReturn($this->serializerDefinition);

        $this->containerBuilder->expects($this->exactly(2))
            ->method('findTaggedServiceIds')
            ->willReturnMap(
                [
                    ['oro_importexport.normalizer', false, $normalizers],
                    ['serializer.encoder', false, $encoders],
                ]
            );

        $this->serializerDefinition->expects($this->once())
            ->method('getArgument')
            ->with(1)
            ->willReturn([new Reference('bazz')]);

        $this->serializerDefinition->expects($this->exactly(2))
            ->method('replaceArgument')
            ->withConsecutive(
                [0, $expectedNormalizers],
                [1, $expectedEncoders]
            );

        $pass = new AddNormalizerCompilerPass();
        $pass->process($this->containerBuilder);
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        return [
            'sort_by_priority' => [
                'normalizers' => [
                    'foo_1' => [['priority' => 1]],
                    'bar_0' => [[]],
                    'baz_2' => [['priority' => 2]],
                ],
                'encoders' => [
                    'foo' => [[]],
                    'bar' => [[]],
                ],
                'expectedNormalizers' => [
                    new Reference('baz_2'),
                    new Reference('foo_1'),
                    new Reference('bar_0'),
                ],
                'expectedEncoders' => [
                    new Reference('bazz'),
                    new Reference('foo'),
                    new Reference('bar'),
                ]
            ],
            'default_order' => [
                'normalizers' => [
                    'foo' => [[]],
                    'bar' => [[]],
                    'baz' => [[]],
                ],
                'encoders' => [
                    'foo' => [[]],
                    'bar' => [[]],
                ],
                'expectedNormalizers' => [
                    new Reference('foo'),
                    new Reference('bar'),
                    new Reference('baz'),
                ],
                'expectedEncoders' => [
                    new Reference('bazz'),
                    new Reference('foo'),
                    new Reference('bar'),
                ]
            ],
        ];
    }

    //@codingStandardsIgnoreStart
    // @codingStandardIgnoreEnd
    public function testProcessFailsWhenNoNormalizers()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'You must tag at least one service as "oro_importexport.normalizer"'
            . ' to use the import export Serializer service'
        );

        $this->containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with('oro_importexport.serializer')
            ->willReturn($this->serializerDefinition);

        $this->containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('oro_importexport.normalizer')
            ->willReturn([]);

        $pass = new AddNormalizerCompilerPass();
        $pass->process($this->containerBuilder);
    }

    //@codingStandardsIgnoreStart
    // @codingStandardIgnoreEnd
    public function testProcessFailsWhenNoEncoders()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'You must tag at least one service as "serializer.encoder" to use the import export Serializer service'
        );

        $this->containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with('oro_importexport.serializer')
            ->willReturn($this->serializerDefinition);

        $this->containerBuilder->expects($this->exactly(2))
            ->method('findTaggedServiceIds')
            ->willReturnMap(
                [
                    ['oro_importexport.normalizer', false, [new Reference('foo')]],
                    ['serializer.encoder', false, []],
                ]
            );

        $pass = new AddNormalizerCompilerPass();
        $pass->process($this->containerBuilder);
    }
}
