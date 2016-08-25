<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ExpressionCompilerPass;

class ExpressionCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();
        $encodingServiceDef = $this->getMockBuilder(Definition::class)->getMock();
        $languageServiceDef = $this->getMockBuilder(Definition::class)->getMock();

        $encoderServiceIds = [
            'json_encoder' => [
                ['format' => 'json']
            ],
            'xml_encoder'  => [
                ['format' => 'xml']
            ]
        ];

        $container->expects($this->at(0))
            ->method('hasDefinition')
            ->with(ExpressionCompilerPass::EXPRESSION_ENCODING_SERVICE)
            ->willReturn(true);

        $container->expects($this->at(1))
            ->method('findTaggedServiceIds')
            ->with(ExpressionCompilerPass::EXPRESSION_ENCODER_TAG)
            ->will($this->returnValue($encoderServiceIds));

        $container->expects($this->at(2))
            ->method('getDefinition')
            ->with(ExpressionCompilerPass::EXPRESSION_ENCODING_SERVICE)
            ->willReturn($encodingServiceDef);

        $encodingServiceDef->expects($this->once())
            ->method('replaceArgument')
            ->with(
                1,
                [
                    'json' => 'json_encoder',
                    'xml'  => 'xml_encoder'
                ]
            );

        $container->expects($this->at(3))
            ->method('hasDefinition')
            ->with(ExpressionCompilerPass::EXPRESSION_LANGUAGE_SERVICE)
            ->willReturn(true);

        $container->expects($this->at(4))
            ->method('findTaggedServiceIds')
            ->with(ExpressionCompilerPass::EXPRESSION_LANGUAGE_PROVIDER_TAG)
            ->will($this->returnValue(['provider1' => [], 'provider2' => []]));

        $container->expects($this->at(5))
            ->method('getDefinition')
            ->with(ExpressionCompilerPass::EXPRESSION_LANGUAGE_SERVICE)
            ->willReturn($languageServiceDef);

        $languageServiceDef->expects($this->once())
            ->method('replaceArgument')
            ->with(1, [
                new Reference('provider1'),
                new Reference('provider2'),
            ]);

        $compilerPass = new ExpressionCompilerPass();
        $compilerPass->process($container);
    }
}
