<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ExpressionCompilerPass;

class ExpressionCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadExpressionEncoders()
    {
        $container          = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();
        $encodingServiceDef = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();

        $encoderServiceIds = [
            'json_encoder' => [
                ['format' => 'json']
            ],
            'xml_encoder'  => [
                ['format' => 'xml']
            ]
        ];

        $container->expects($this->any())
            ->method('hasDefinition')
            ->will(
                $this->returnValueMap(
                    [
                        [ExpressionCompilerPass::EXPRESSION_ENCODING_SERVICE, true]
                    ]
                )
            );
        $container->expects($this->once())
            ->method('getDefinition')
            ->will(
                $this->returnValueMap(
                    [
                        [ExpressionCompilerPass::EXPRESSION_ENCODING_SERVICE, $encodingServiceDef]
                    ]
                )
            );
        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(ExpressionCompilerPass::EXPRESSION_ENCODER_TAG)
            ->will($this->returnValue($encoderServiceIds));

        $encodingServiceDef->expects($this->once())
            ->method('replaceArgument')
            ->with(
                1,
                [
                    'json' => 'json_encoder',
                    'xml'  => 'xml_encoder'
                ]
            );

        $compilerPass = new ExpressionCompilerPass();
        $compilerPass->process($container);
    }
}
