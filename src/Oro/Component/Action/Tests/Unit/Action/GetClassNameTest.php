<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\GetClassName;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class GetClassNameTest extends \PHPUnit\Framework\TestCase
{
    /** @var GetClassName */
    private $action;

    protected function setUp(): void
    {
        $this->action = new GetClassName(new ContextAccessor());
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    public function testInitializeAttributeException()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute name parameter is required');

        $this->assertEquals($this->action, $this->action->initialize(['object' => new \stdClass()]));
    }

    public function testInitializeObjectException()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Object parameter is required');

        $this->assertEquals($this->action, $this->action->initialize([]));
    }

    public function testInitializeAttributeWrongException()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute must be valid property definition.');

        $this->assertEquals(
            $this->action,
            $this->action->initialize(['object' => new \stdClass(), 'attribute' => 'wrong'])
        );
    }

    /**
     * @dataProvider objectDataProvider
     */
    public function testExecute(mixed $object, ?string $class)
    {
        $options = ['object' => $object, 'attribute' => new PropertyPath('attribute')];
        $context = new ItemStub($options);

        $this->action->initialize($options);
        $this->action->execute($context);
        $this->assertEquals($class, $context->getData()['attribute']);
    }

    public function objectDataProvider(): array
    {
        return [
            [new \stdClass(), 'stdClass'],
            ['string', null],
            [new PropertyPath('unknown'), null]
        ];
    }
}
