<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\DataAccessorInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\LayoutBundle\Layout\Processor\ExpressionProcessor;
use Oro\Bundle\LayoutBundle\Layout\Block\Extension\ExpressionExtension;

class ExpressionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExpressionProcessor|\PHPUnit_Framework_MockObject_MockObject */
    protected $processor;

    /** @var ExpressionExtension */
    protected $extension;

    protected function setUp()
    {
        $this->processor = $this->getMockBuilder('Oro\Bundle\LayoutBundle\Layout\Processor\ExpressionProcessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new ExpressionExtension($this->processor);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(BaseType::NAME, $this->extension->getExtendedType());
    }

    /**
     * @dataProvider deferredDataProvider
     * @param bool $deferred
     */
    public function testNormalizeOptions($deferred)
    {
        $context = $this->getContextMock($deferred);
        $data = $this->getDataAccessorMock();
        $options = [];

        $this->processor->expects($deferred ? $this->never() : $this->once())
            ->method('processExpressions')
            ->with($options, $context, $data, true, 'json');

        $this->extension->normalizeOptions($options, $context, $data);
    }

    /**
     * @dataProvider deferredDataProvider
     * @param bool $deferred
     */
    public function testFinishView($deferred)
    {
        $context = $this->getContextMock($deferred);
        $data = $this->getDataAccessorMock();

        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $block->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));
        $view = new BlockView();
        $view->vars = ['test'];

        $this->processor->expects($deferred ? $this->once(): $this->never())
            ->method('processExpressions')
            ->with($view->vars, $context, $data, true, 'json');

        $this->extension->finishView($view, $block, []);
    }

    /**
     * @return array
     */
    public function deferredDataProvider()
    {
        return [
            ['expressions_evaluate_deferred' => true],
            ['expressions_evaluate_deferred' => false]
        ];
    }

    /**
     * @param bool $evaluateDeferred
     * @return LayoutContext
     */
    protected function getContextMock($evaluateDeferred)
    {
        $context = new LayoutContext();
        $context->set('expressions_evaluate_deferred', $evaluateDeferred);
        $context->set('expressions_evaluate', true);
        $context->set('expressions_encoding', 'json');

        return $context;
    }

    /**
     * @return DataAccessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDataAccessorMock()
    {
        return $this->getMock('Oro\Component\Layout\DataAccessorInterface');
    }
}
