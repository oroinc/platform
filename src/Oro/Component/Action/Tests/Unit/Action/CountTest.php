<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Component\Action\Action\Count;
use Oro\Component\Action\Tests\Unit\Action\Stub\StubStorage;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class CountTest extends \PHPUnit\Framework\TestCase
{
    /** @var Count */
    protected $action;

    protected function setUp()
    {
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();

        $this->action = new Count(new ContextAccessor());
        $this->action->setDispatcher($dispatcher);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Parameter `value` is required.
     */
    public function testInitializeArrayException()
    {
        $this->assertEquals($this->action, $this->action->initialize([]));
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Parameter `attribute` is required.
     */
    public function testInitializeAttributeException()
    {
        $this->assertEquals($this->action, $this->action->initialize(['value' => []]));
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Parameter `attribute` must be a valid property definition.
     */
    public function testInitializeAttributeWrongException()
    {
        $this->assertEquals($this->action, $this->action->initialize(['value' => [], 'attribute' => 'test']));
    }

    /**
     * @dataProvider objectDataProvider
     *
     * @param array|Collection $array
     * @param int $count
     */
    public function testExecute($array, $count)
    {
        $context = new StubStorage();

        $this->action->initialize(['value' => $array, 'attribute' => new PropertyPath('test')]);
        $this->action->execute($context);

        $this->assertEquals(['test' => $count], $context->getValues());
    }

    /**
     * @return array
     */
    public function objectDataProvider()
    {
        return [
            [[1, 2, 3], 3],
            [new ArrayCollection([1, 2, 3, 4, 5]), 5],
            [new \stdClass(), 0]
        ];
    }
}
