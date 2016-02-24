<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\ActionBundle\Action\RefreshGrid;
use Oro\Bundle\ActionBundle\Model\ActionData;

use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class RefreshGridTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var RefreshGrid */
    protected $action;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->action = new RefreshGrid(new ContextAccessor());
        $this->action->setDispatcher($this->eventDispatcher);
    }

    protected function tearDown()
    {
        unset($this->action, $this->eventDispatcher);
    }

    public function testInitialize()
    {
        $gridname = 'test_grid';

        $this->assertInstanceOf(
            'Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface',
            $this->action->initialize([$gridname])
        );

        $this->assertAttributeEquals([$gridname], 'gridNames', $this->action);
    }

    /**
     * @expectedException \Oro\Bundle\ActionBundle\Exception\InvalidParameterException
     * @expectedExceptionMessage Gridname parameter must be specified
     */
    public function testInitializeException()
    {
        $this->action->initialize([]);
    }

    public function testExecuteMethod()
    {
        $gridnames = ['test_grid', new PropertyPath('param')];

        $context = new ActionData(['param' => 'value']);

        $this->action->initialize($gridnames);
        $this->action->execute($context);

        $this->assertEquals(['param' => 'value', 'refreshGrid' => ['test_grid', 'value']], $context->getValues());
    }
}
