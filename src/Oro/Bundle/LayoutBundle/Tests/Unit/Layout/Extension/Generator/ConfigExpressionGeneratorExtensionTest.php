<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension\Generator;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use Oro\Bundle\LayoutBundle\Layout\Extension\Generator\ConfigExpressionGeneratorExtension;

class ConfigExpressionGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExpressionLanguage|\PHPUnit_Framework_MockObject_MockObject */
    protected $expressionLanguage;

    /** @var ConfigExpressionGeneratorExtension */
    protected $extension;

    protected function setUp()
    {
        $this->expressionLanguage = $this->getMock(ExpressionLanguage::class);

        $this->extension = new ConfigExpressionGeneratorExtension($this->expressionLanguage);
    }

    public function testNoConditions()
    {
        $visitors = new VisitorCollection();

        $this->expressionLanguage->expects($this->never())
            ->method('parse');

        $this->extension->prepare(
            $this->createGeneratorData([]),
            $visitors
        );

        $this->assertCount(0, $visitors);
    }

    public function testEmptyConditions()
    {
        $visitors = new VisitorCollection();

        $this->expressionLanguage->expects($this->never())
            ->method('parse');

        $this->extension->prepare(
            $this->createGeneratorData([
                ConfigExpressionGeneratorExtension::NODE_CONDITIONS => null
            ]),
            $visitors
        );

        $this->assertCount(0, $visitors);
    }

    public function testHasConditions()
    {
        $visitors = new VisitorCollection();

        $expression = $this->getMockBuilder(ParsedExpression::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->expressionLanguage->expects($this->once())
            ->method('parse')
            ->with('true')
            ->will($this->returnValue($expression));

        $this->extension->prepare(
            $this->createGeneratorData([
                ConfigExpressionGeneratorExtension::NODE_CONDITIONS => 'true'
            ]),
            $visitors
        );

        $this->assertCount(1, $visitors);
        $this->assertInstanceOf(
            'Oro\Bundle\LayoutBundle\Layout\Extension\Generator\ConfigExpressionConditionVisitor',
            $visitors->current()
        );
    }

    public function testUnknownConditions()
    {
        $visitors = new VisitorCollection();

        $this->expressionLanguage->expects($this->once())
            ->method('parse')
            ->with('unknown')
            ->will($this->returnValue(null));

        $this->extension->prepare(
            $this->createGeneratorData([
                ConfigExpressionGeneratorExtension::NODE_CONDITIONS => 'unknown'
            ]),
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

        $this->expressionLanguage->expects($this->once())
            ->method('parse')
            ->with('true')
            ->will($this->throwException(new \Exception('some error')));

        $this->extension->prepare(
            $this->createGeneratorData([
                ConfigExpressionGeneratorExtension::NODE_CONDITIONS => 'true'
            ]),
            $visitors
        );
    }

    /**
     * @param array $source
     * @return GeneratorData
     */
    protected function createGeneratorData(array $source)
    {
        return new GeneratorData($source);
    }
}
