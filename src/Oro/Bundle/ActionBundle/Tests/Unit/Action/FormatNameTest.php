<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Action;

use Oro\Bundle\ActionBundle\Action\FormatName;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class FormatNameTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /** @var FormatName */
    private $action;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);

        $this->action = new FormatName($this->contextAccessor, $this->entityNameResolver);
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    public function testInitializeExceptionNoObject()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Object parameter is required');

        $this->action->initialize(['attribute' => $this->createMock(PropertyPath::class)]);
    }

    public function testInitializeExceptionNoAttribute()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute name parameter is required');

        $this->action->initialize(['object' => new \stdClass()]);
    }

    public function testInitialize()
    {
        $options = ['object' => new \stdClass(), 'attribute' => $this->createMock(PropertyPath::class)];
        self::assertEquals($this->action, $this->action->initialize($options));
        self::assertEquals($options, ReflectionUtil::getPropertyValue($this->action, 'options'));
    }

    public function testExecute()
    {
        $object = new \stdClass();
        $attribute = $this->createMock(PropertyPath::class);
        $context = [];
        $options = ['object' => $object, 'attribute' => $attribute];

        self::assertEquals($this->action, $this->action->initialize($options));

        $this->entityNameResolver->expects(self::once())
            ->method('getName')
            ->with($object)
            ->willReturn('FORMATTED');
        $this->contextAccessor->expects(self::once())
            ->method('setValue')
            ->with($context, $attribute, 'FORMATTED');
        $this->contextAccessor->expects(self::once())
            ->method('getValue')
            ->with($context, $object)
            ->willReturnArgument(1);

        $this->action->execute($context);
    }
}
