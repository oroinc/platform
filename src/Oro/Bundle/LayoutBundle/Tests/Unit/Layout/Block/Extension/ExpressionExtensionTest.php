<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\Options;
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

    public function testNormalizeOptions()
    {
        $context = $this->getContextMock();
        $data = $this->getDataAccessorMock();
        $options = new Options();

        $this->processor->expects($this->once())
            ->method('processExpressions')
            ->with([], $context, null, true, null);

        $this->extension->normalizeOptions($options, $context, $data);
    }

    public function testFinishView()
    {
        $context = $this->getContextMock();
        $data = $this->getDataAccessorMock();

        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $block->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));
        $view = new BlockView();
        $view->vars[] = 'test';

        $this->processor->expects($this->once())
            ->method('processExpressions')
            ->with($view->vars, $context, $data, true, 'json');

        $this->extension->finishView($view, $block);
    }

    /**
     * @return LayoutContext
     */
    protected function getContextMock()
    {
        $context = new LayoutContext();
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
