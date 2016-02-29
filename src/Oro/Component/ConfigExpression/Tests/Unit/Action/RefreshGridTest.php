<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\ConfigExpression\Action\RefreshGrid;
use Oro\Component\ConfigExpression\Model\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Action\Stub\StubStorage;

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
            'Oro\Component\ConfigExpression\Action\ActionInterface',
            $this->action->initialize([$gridname])
        );

        $this->assertAttributeEquals([$gridname], 'gridNames', $this->action);
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidParameterException
     * @expectedExceptionMessage Gridname parameter must be specified
     */
    public function testInitializeException()
    {
        $this->action->initialize([]);
    }

    public function testExecuteMethod()
    {
        $gridname = 'test_grid';

        $context = new StubStorage(['param' => 'value']);

        $this->action->initialize([$gridname]);
        $this->action->execute($context);

        $this->assertEquals(['param' => 'value', 'refreshGrid' => [$gridname]], $context->getValues());
    }
}
