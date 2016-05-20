<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension\Generator;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;

use Oro\Bundle\LayoutBundle\Layout\Extension\Generator\ConfigExpressionGeneratorExtension;

class ConfigExpressionGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $expressionAssembler;

    /** @var ConfigExpressionGeneratorExtension */
    protected $extension;

    protected function setUp()
    {
        $this->expressionAssembler = $this->getMock('Oro\Component\ConfigExpression\AssemblerInterface');

        $this->extension = new ConfigExpressionGeneratorExtension($this->expressionAssembler);
    }

    public function testNoConditions()
    {
        $visitors = new VisitorCollection();

        $this->expressionAssembler->expects($this->never())
            ->method('assemble');

        $this->extension->prepare(
            [],
            $visitors
        );

        $this->assertCount(0, $visitors);
    }

    public function testEmptyConditions()
    {
        $visitors = new VisitorCollection();

        $this->expressionAssembler->expects($this->never())
            ->method('assemble');

        $this->extension->prepare(
            [
                ConfigExpressionGeneratorExtension::NODE_CONDITIONS => null
            ],
            $visitors
        );

        $this->assertCount(0, $visitors);
    }

    public function testHasConditions()
    {
        $visitors = new VisitorCollection();

        $this->expressionAssembler->expects($this->once())
            ->method('assemble')
            ->with([['@true' => null]])
            ->will($this->returnValue(new Condition\TrueCondition()));

        $this->extension->prepare(
            [
                ConfigExpressionGeneratorExtension::NODE_CONDITIONS => [
                    ['@true' => null]
                ]
            ],
            $visitors
        );

        $this->assertCount(1, $visitors);
        $this->assertInstanceOf(
            'Oro\Bundle\LayoutBundle\Layout\Extension\Generator\ConfigExpressionConditionVisitor',
            $visitors[0]
        );
    }

    public function testUnknownConditions()
    {
        $visitors = new VisitorCollection();

        $this->expressionAssembler->expects($this->once())
            ->method('assemble')
            ->with([['@true' => null]])
            ->will($this->returnValue(null));

        $this->extension->prepare(
            [
                ConfigExpressionGeneratorExtension::NODE_CONDITIONS => [
                    ['@true' => null]
                ]
            ],
            $visitors
        );

        $this->assertCount(0, $visitors);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\SyntaxException
     * @expectedExceptionMessage Syntax error: invalid conditions. some error at "conditions"
     */
    public function testInvalidConditions()
    {
        $visitors = new VisitorCollection();

        $this->expressionAssembler->expects($this->once())
            ->method('assemble')
            ->with([['@true' => null]])
            ->will($this->throwException(new \Exception('some error')));

        $this->extension->prepare(
            [
                ConfigExpressionGeneratorExtension::NODE_CONDITIONS => [
                    ['@true' => null]
                ]
            ],
            $visitors
        );
    }
}
