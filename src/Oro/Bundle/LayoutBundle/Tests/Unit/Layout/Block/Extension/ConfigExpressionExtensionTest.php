<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\ConfigExpression\AssemblerInterface;
use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Func;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\OptionValueBag;

use Oro\Bundle\LayoutBundle\Layout\Block\Extension\ConfigExpressionExtension;
use Oro\Bundle\LayoutBundle\Layout\Encoder\ConfigExpressionEncoderRegistry;
use Oro\Bundle\LayoutBundle\Layout\Encoder\JsonConfigExpressionEncoder;

class ConfigExpressionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var AssemblerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $expressionAssembler;

    /** @var ConfigExpressionExtension */
    protected $extension;

    protected function setUp()
    {
        $this->expressionAssembler = $this->getMock('Oro\Component\ConfigExpression\AssemblerInterface');

        /** @var ConfigExpressionEncoderRegistry|\PHPUnit_Framework_MockObject_MockObject $encoderRegistry */
        $encoderRegistry = $this
            ->getMockBuilder('Oro\Bundle\LayoutBundle\Layout\Encoder\ConfigExpressionEncoderRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $encoderRegistry->expects($this->any())
            ->method('getEncoder')
            ->with('json')
            ->will($this->returnValue(new JsonConfigExpressionEncoder()));

        $this->extension = new ConfigExpressionExtension(
            $this->expressionAssembler,
            $encoderRegistry
        );
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(BaseType::NAME, $this->extension->getExtendedType());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBuildBlockEvaluatesAllExpressions()
    {
        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $data  = $this->getMock('Oro\Component\Layout\DataAccessorInterface');

        /** @var BlockBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $block */
        $block = $this->getMock('Oro\Component\Layout\BlockBuilderInterface');
        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $block->expects($this->once())
            ->method('getDataAccessor')
            ->will($this->returnValue($data));

        $expr = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $expr->expects($this->once())
            ->method('evaluate')
            ->with(['context' => $context, 'data' => $data])
            ->will($this->returnValue(true));

        $classExpr = new Func\GetValue();
        $classExpr->initialize([new PropertyPath('context.css_class')]);
        $classExpr->setContextAccessor(new ContextAccessor());
        $classAttr = new OptionValueBag();
        $classAttr->add(['@value' => ['$context.css_class']]);
        $expectedClassAttr = new OptionValueBag();
        $expectedClassAttr->add('test_class');

        $options = [];
        $options['expr_object']           = $expr;
        $options['expr_array']            = ['@true' => null];
        $options['not_expr_array']        = ['\@true' => null];
        $options['scalar']                = 123;
        $options['attr']['enabled']       = ['@true' => null];
        $options['attr']['data-scalar']   = 'foo';
        $options['attr']['data-expr']     = ['@true' => null];
        $options['attr']['class']         = $classAttr;
        $options['label_attr']['enabled'] = ['@true' => null];
        $options['array_with_expr']       = ['item1' => 'val1', 'item2' => ['@true' => null]];

        $this->expressionAssembler->expects($this->exactly(6))
            ->method('assemble')
            ->will(
                $this->returnValueMap(
                    [
                        [['@true' => null], new Condition\True()],
                        [['@value' => ['$context.css_class']], $classExpr]
                    ]
                )
            );

        $context['expressions_evaluate'] = true;
        $this->extension->buildBlock($block, $options);

        $this->assertSame(
            true,
            $options['expr_object'],
            'Failed asserting that an expression is evaluated'
        );
        $this->assertSame(
            true,
            $options['expr_array'],
            'Failed asserting that an expression is assembled and evaluated'
        );
        $this->assertSame(
            ['@true' => null],
            $options['not_expr_array'],
            'Failed asserting that a backslash at the begin of the array key is removed'
        );
        $this->assertSame(
            123,
            $options['scalar'],
            'Failed asserting that a scalar value is not changed'
        );
        $this->assertSame(
            true,
            $options['attr']['enabled'],
            'Failed asserting that an expression in "attr" is assembled and evaluated'
        );
        $this->assertSame(
            'foo',
            $options['attr']['data-scalar'],
            'Failed asserting that "attr.data-scalar" exists'
        );
        $this->assertSame(
            true,
            $options['attr']['data-expr'],
            'Failed asserting that "attr.data-expr" is assembled and evaluated'
        );
        $this->assertEquals(
            $expectedClassAttr,
            $options['attr']['class'],
            'Failed asserting that "attr.class" is assembled and evaluated'
        );
        $this->assertSame(
            true,
            $options['label_attr']['enabled'],
            'Failed asserting that an expression in "label_attr" is assembled and evaluated'
        );
        $this->assertSame(
            ['item1' => 'val1', 'item2' => true],
            $options['array_with_expr'],
            'Failed asserting that an expression is assembled and evaluated in nested array'
        );
    }

    public function testBuildBlockDoNothingIfEvaluationOfExpressionsDisabledAndEncodingIsNotSet()
    {
        $context = new LayoutContext();

        /** @var BlockBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $block */
        $block = $this->getMock('Oro\Component\Layout\BlockBuilderInterface');
        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $block->expects($this->never())
            ->method('getDataAccessor');

        $expr = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $expr->expects($this->never())
            ->method('evaluate');
        $expr->expects($this->never())
            ->method('toArray');

        $options = [];
        $options['expr_object']           = $expr;
        $options['expr_array']            = ['@true' => null];
        $options['not_expr_array']        = ['\@true' => null];
        $options['scalar']                = 123;
        $options['attr']['enabled']       = ['@true' => null];
        $options['label_attr']['enabled'] = ['@true' => null];

        $this->expressionAssembler->expects($this->never())
            ->method('assemble');

        $initialVars = $options;

        $context['expressions_evaluate'] = false;
        $this->extension->buildBlock($block, $options);

        $this->assertSame($initialVars, $options);
    }

    public function testBuildBlockEncodesAllExpressions()
    {
        $context = new LayoutContext();
        $data    = $this->getMock('Oro\Component\Layout\DataAccessorInterface');

        /** @var BlockBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $block */
        $block = $this->getMock('Oro\Component\Layout\BlockBuilderInterface');
        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $block->expects($this->once())
            ->method('getDataAccessor')
            ->will($this->returnValue($data));

        $expr = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $expr->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue(['@true' => null]));

        $classExpr = new Func\GetValue();
        $classExpr->initialize([new PropertyPath('context.css_class')]);
        $classAttr = new OptionValueBag();
        $classAttr->add(['@value' => ['$context.css_class']]);
        $expectedClassAttr = new OptionValueBag();
        $expectedClassAttr->add('{"@value":{"parameters":["$context.css_class"]}}');

        $options = [];
        $options['expr_object']           = $expr;
        $options['expr_array']            = ['@true' => null];
        $options['not_expr_array']        = ['\@true' => null];
        $options['scalar']                = 123;
        $options['attr']['enabled']       = ['@true' => null];
        $options['attr']['class']         = $classAttr;
        $options['label_attr']['enabled'] = ['@true' => null];

        $this->expressionAssembler->expects($this->exactly(4))
            ->method('assemble')
            ->will(
                $this->returnValueMap(
                    [
                        [['@true' => null], new Condition\True()],
                        [['@value' => ['$context.css_class']], $classExpr]
                    ]
                )
            );

        $context['expressions_evaluate'] = false;
        $context['expressions_encoding'] = 'json';
        $this->extension->buildBlock($block, $options);

        $this->assertSame(
            '{"@true":null}',
            $options['expr_object'],
            'Failed asserting that an expression is encoded'
        );
        $this->assertSame(
            '{"@true":null}',
            $options['expr_array'],
            'Failed asserting that an expression is assembled and encoded'
        );
        $this->assertSame(
            ['@true' => null],
            $options['not_expr_array'],
            'Failed asserting that a backslash at the begin of the array key is removed'
        );
        $this->assertSame(
            123,
            $options['scalar'],
            'Failed asserting that a scalar value is not changed'
        );
        $this->assertSame(
            '{"@true":null}',
            $options['attr']['enabled'],
            'Failed asserting that an expression in "attr" is assembled and encoded'
        );
        $this->assertEquals(
            $expectedClassAttr,
            $options['attr']['class'],
            'Failed asserting that "attr.class" is assembled and encoded'
        );
        $this->assertSame(
            '{"@true":null}',
            $options['label_attr']['enabled'],
            'Failed asserting that an expression in "label_attr" is assembled and encoded'
        );
    }
}
