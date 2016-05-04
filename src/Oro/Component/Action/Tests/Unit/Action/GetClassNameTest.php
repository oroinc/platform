<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Oro\Component\Action\Action\GetClassName;
use Oro\Component\Action\Model\ContextAccessor;

class GetClassNameTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GetClassName
     */
    protected $action;

    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();
        $this->action = new GetClassName($this->contextAccessor);
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Attribute name parameter is required
     * @throws \Oro\Component\Action\Exception\InvalidParameterException
     */
    public function testInitializeAttributeException()
    {
        $this->assertEquals($this->action, $this->action->initialize(['object' => new \stdClass()]));
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Object parameter is required
     * @throws \Oro\Component\Action\Exception\InvalidParameterException
     */
    public function testInitializeObjectException()
    {
        $this->assertEquals($this->action, $this->action->initialize([]));
    }


    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Attribute must be valid property definition.
     * @throws \Oro\Component\Action\Exception\InvalidParameterException
     */
    public function testInitializeAttributeWrongException()
    {
        $this->assertEquals(
            $this->action,
            $this->action->initialize(['object' => new \stdClass(), 'attribute' => 'wrong'])
        );
    }

    /**
     * @dataProvider objectDataProvider
     * @param mixed $object
     * @param string|null $class
     */
    public function testExecute($object, $class)
    {
        $options = ['object' => $object, 'attribute' => new PropertyPath('attribute')];
        $context = new ItemStub($options);

        $this->action->initialize($options);
        $this->action->execute($context);
        $this->assertEquals($class, $context->getData()['attribute']);
    }

    /**
     * @return array
     */
    public function objectDataProvider()
    {
        return [
            [new \stdClass(), 'stdClass'],
            ['string', null],
            [new PropertyPath('unknown'), null]
        ];
    }
}
