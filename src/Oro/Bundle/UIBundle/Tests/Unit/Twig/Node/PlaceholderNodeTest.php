<?php
namespace Oro\Bundle\UIBundle\Tests\Unit\Twig\Node;

use Oro\Bundle\UIBundle\Twig\Node\PlaceholderNode;

class PlaceholderNodeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var int
     */
    protected $lineno = 100;

    /**
     * @var string
     */
    protected $tag = 'placeholder';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $compiler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $nameNode;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $variablesNode;

    /**
     * @var PlaceholderNode
     */
    protected $node;

    public function setUp()
    {
        $this->compiler = $this->getMockBuilder('Twig_Compiler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->nameNode = $this->createNode();
        $this->variablesNode = $this->createNode();

        $this->node = new PlaceholderNode(
            $this->nameNode,
            $this->variablesNode,
            $this->lineno,
            $this->tag
        );
    }

    public function testCompile()
    {
        $this->compiler->expects($this->at(0))
            ->method('addDebugInfo')
            ->with($this->isInstanceOf('Twig_Node_Print'))
            ->will($this->returnSelf());

        $this->compiler->expects($this->at(1))
            ->method('write')
            ->with('echo ')
            ->will($this->returnSelf());

        $this->compiler->expects($this->at(2))
            ->method('subcompile')
            ->with(
                $this->callback(
                    function ($functionExpressionNode) {
                        /** @var \Twig_Node_Expression_Function $functionExpressionNode */
                        $this->assertInstanceOf('Twig_Node_Expression_Function', $functionExpressionNode);
                        $this->assertEquals('placeholder', $functionExpressionNode->getAttribute('name'));
                        $this->assertEquals($this->lineno, $functionExpressionNode->getLine());

                        /** @var \Twig_Node $argumentsNode */
                        $argumentsNode = $functionExpressionNode->getNode('arguments');
                        $this->assertEquals($this->nameNode, $argumentsNode->getNode('name'));
                        $this->assertEquals($this->variablesNode, $argumentsNode->getNode('variables'));
                        return true;
                    }
                )
            )
            ->will($this->returnSelf());

        $this->compiler->expects($this->at(3))
            ->method('raw')
            ->with(";\n")
            ->will($this->returnSelf());

        $this->node->compile($this->compiler);
    }

    protected function createNode()
    {
        return $this->getMockBuilder('Twig_Node')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
