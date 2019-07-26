<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\AddNormalizerCompilerPass;
use Symfony\Component\DependencyInjection\Reference;

class AddNormalizerCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $serializerDefinition;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $containerBuilder;

    protected function setUp()
    {
        $this->serializerDefinition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $this->containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider processDataProvider
     *
     * @param array $normalizers
     * @param array $encoders
     * @param array $expectedNormalizers
     * @param array $expectedEncoders
     */
    public function testProcess(
        array $normalizers,
        array $encoders,
        array $expectedNormalizers,
        array $expectedEncoders
    ) {
        $this->containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with(AddNormalizerCompilerPass::SERIALIZER_SERVICE)
            ->willReturn($this->serializerDefinition);

        $this->containerBuilder->expects($this->exactly(2))
            ->method('findTaggedServiceIds')
            ->willReturnMap(
                [
                    [AddNormalizerCompilerPass::ATTRIBUTE_NORMALIZER_TAG, false, $normalizers],
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
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage You must tag at least one service as "oro_importexport.normalizer" to use the import export Serializer service
     */
    // @codingStandardIgnoreEnd
    public function testProcessFailsWhenNoNormalizers()
    {
        $this->containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(AddNormalizerCompilerPass::ATTRIBUTE_NORMALIZER_TAG)
            ->will($this->returnValue(array()));

        $pass = new AddNormalizerCompilerPass();
        $pass->process($this->containerBuilder);
    }
}
