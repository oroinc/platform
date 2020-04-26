<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Action;

use Oro\Bundle\ActionBundle\Action\FormatName;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class FormatNameTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormatName */
    protected $action;

    /** @var MockObject|ContextAccessor */
    protected $contextAccessor;

    /** @var MockObject|EntityNameResolver */
    protected $entityNameResolver;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->getMockBuilder(ContextAccessor::class)->disableOriginalConstructor()->getMock();
        $this->entityNameResolver = $this->getMockBuilder(EntityNameResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new class($this->contextAccessor, $this->entityNameResolver) extends FormatName {
            public function xgetOptions(): array
            {
                return $this->options;
            }
        };

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    public function testInitializeExceptionNoObject()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Object parameter is required');

        $this->action->initialize(['attribute' => $this->getPropertyPath()]);
    }

    public function testInitializeExceptionNoAttribute()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute name parameter is required');

        $this->action->initialize(['object' => new \stdClass()]);
    }

    public function testInitialize()
    {
        $options = ['object' => new \stdClass(), 'attribute' => $this->getPropertyPath()];
        static::assertEquals($this->action, $this->action->initialize($options));
        static::assertEquals($options, $this->action->xgetOptions());
    }

    public function testExecute()
    {
        $object = new \stdClass();
        $attribute = $this->getPropertyPath();
        $context = [];
        $options = ['object' => $object, 'attribute' => $attribute];

        static::assertEquals($this->action, $this->action->initialize($options));

        $this->entityNameResolver->expects(static::once())
            ->method('getName')
            ->with($object)
            ->willReturn('FORMATTED');
        $this->contextAccessor->expects(static::once())
            ->method('setValue')
            ->with($context, $attribute, 'FORMATTED');
        $this->contextAccessor->expects(static::once())
            ->method('getValue')
            ->with($context, $object)
            ->willReturnArgument(1);

        $this->action->execute($context);
    }

    protected function getPropertyPath()
    {
        return $this->getMockBuilder(PropertyPath::class)->disableOriginalConstructor()->getMock();
    }
}
