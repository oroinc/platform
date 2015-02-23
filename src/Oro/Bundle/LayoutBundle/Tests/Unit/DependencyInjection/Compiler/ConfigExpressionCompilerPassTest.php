<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ConfigExpressionCompilerPass;

class ConfigExpressionCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadExpressions()
    {
        $container      = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();
        $extensionDef   = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();
        $expression1Def = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();
        $expression2Def = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();

        $expressionServiceIds = [
            'expression1' => [
                ['alias' => 'expr1']
            ],
            'expression2' => [
                ['alias' => 'expr2|expr2_alias']
            ]
        ];

        $container->expects($this->any())
            ->method('hasDefinition')
            ->will(
                $this->returnValueMap(
                    [
                        [ConfigExpressionCompilerPass::EXTENSION_SERVICE, true]
                    ]
                )
            );
        $container->expects($this->exactly(3))
            ->method('getDefinition')
            ->will(
                $this->returnValueMap(
                    [
                        [ConfigExpressionCompilerPass::EXTENSION_SERVICE, $extensionDef],
                        ['expression1', $expression1Def],
                        ['expression2', $expression2Def]
                    ]
                )
            );
        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(ConfigExpressionCompilerPass::EXPRESSION_TAG)
            ->will($this->returnValue($expressionServiceIds));

        $expression1Def->expects($this->once())
            ->method('setScope')
            ->with(ContainerInterface::SCOPE_PROTOTYPE);
        $expression2Def->expects($this->once())
            ->method('setScope')
            ->with(ContainerInterface::SCOPE_PROTOTYPE);

        $extensionDef->expects($this->once())
            ->method('replaceArgument')
            ->with(
                1,
                [
                    'expr1'       => 'expression1',
                    'expr2'       => 'expression2',
                    'expr2_alias' => 'expression2'
                ]
            );

        $compilerPass = new ConfigExpressionCompilerPass();
        $compilerPass->process($container);
    }

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
                        [ConfigExpressionCompilerPass::EXPRESSION_ENCODING_SERVICE, true]
                    ]
                )
            );
        $container->expects($this->once())
            ->method('getDefinition')
            ->will(
                $this->returnValueMap(
                    [
                        [ConfigExpressionCompilerPass::EXPRESSION_ENCODING_SERVICE, $encodingServiceDef]
                    ]
                )
            );
        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(ConfigExpressionCompilerPass::EXPRESSION_ENCODER_TAG)
            ->will($this->returnValue($encoderServiceIds));

        $encodingServiceDef->expects($this->once())
            ->method('replaceArgument')
            ->with(
                2,
                [
                    'json' => 'json_encoder',
                    'xml'  => 'xml_encoder'
                ]
            );

        $compilerPass = new ConfigExpressionCompilerPass();
        $compilerPass->process($container);
    }
}
