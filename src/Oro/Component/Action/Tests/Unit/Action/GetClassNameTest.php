<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\GetClassName;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class GetClassNameTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GetClassName
     */
    protected $action;

    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();
        $this->action = new GetClassName($this->contextAccessor);
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    public function testInitializeAttributeException()
    {
        $this->expectException(\Oro\Component\Action\Exception\InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute name parameter is required');

        $this->assertEquals($this->action, $this->action->initialize(['object' => new \stdClass()]));
    }

    public function testInitializeObjectException()
    {
        $this->expectException(\Oro\Component\Action\Exception\InvalidParameterException::class);
        $this->expectExceptionMessage('Object parameter is required');

        $this->assertEquals($this->action, $this->action->initialize([]));
    }

    public function testInitializeAttributeWrongException()
    {
        $this->expectException(\Oro\Component\Action\Exception\InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute must be valid property definition.');

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
