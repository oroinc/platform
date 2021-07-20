<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\CreateObject;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class CreateObjectTest extends \PHPUnit\Framework\TestCase
{
    /** @var CreateObject */
    protected $action;

    /** @var ContextAccessor */
    protected $contextAccessor;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();
        $this->action = new class($this->contextAccessor) extends CreateObject {
            public function xgetOptions(): array
            {
                return $this->options;
            }
        };

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    protected function tearDown(): void
    {
        unset($this->contextAccessor, $this->action);
    }

    public function testInitializeExceptionNoClassName()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Class name parameter is required');

        $this->action->initialize(['some' => 1, 'attribute' => $this->getPropertyPath()]);
    }

    public function testInitializeExceptionNoAttribute()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute name parameter is required');

        $this->action->initialize(['class' => 'stdClass']);
    }

    public function testInitializeExceptionInvalidAttribute()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute must be valid property definition.');

        $this->action->initialize(['class' => 'stdClass', 'attribute' => 'string']);
    }

    public function testExceptionInvalidData()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Object data must be an array.');

        $this->action->initialize(
            ['class' => 'stdClass', 'attribute' => $this->getPropertyPath(), 'data' => 'string_value']
        );

        $this->action->execute(new ItemStub());
    }

    public function testInitializeExceptionInvalidArguments()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Object constructor arguments must be an array.');

        $this->action->initialize(
            ['class' => 'stdClass', 'attribute' => $this->getPropertyPath(), 'arguments' => 'string_value']
        );
    }

    public function testInitialize()
    {
        $options = ['class' => 'stdClass', 'attribute' => $this->getPropertyPath()];
        static::assertEquals($this->action, $this->action->initialize($options));
        static::assertEquals($options, $this->action->xgetOptions());
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $options, array $contextData = [], array $expectedData = null)
    {
        $context = new ItemStub($contextData);
        $attributeName = (string)$options['attribute'];
        $this->action->initialize($options);
        $this->action->execute($context);
        static::assertNotNull($context->$attributeName);
        static::assertInstanceOf($options['class'], $context->$attributeName);

        if ($context->$attributeName instanceof ItemStub) {
            /** @var ItemStub $entity */
            $entity = $context->$attributeName;
            if (!$expectedData) {
                $expectedData = !empty($options['data']) ? $options['data'] : [];
            }
            static::assertInstanceOf($options['class'], $entity);
            static::assertEquals($expectedData, $entity->getData());
        }
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'without data' => [
                'options' => [
                    'class'     => 'Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub',
                    'attribute' => new PropertyPath('test_attribute'),
                ]
            ],
            'with data' => [
                'options' => [
                    'class'     => 'Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub',
                    'attribute' => new PropertyPath('test_attribute'),
                    'data'      => ['key1' => new PropertyPath('test_attribute'), 'key2' => 'value2'],
                ],
                ['test_attribute' => 'test_value'],
                ['key1' => 'test_value', 'key2' => 'value2']
            ],
            'with arguments' => [
                'options' => [
                    'class'     => '\DateTime',
                    'attribute' => new PropertyPath('test_attribute'),
                    'arguments' => ['now'],
                ]
            ],
            'with complex arguments' => [
                'options' => [
                    'class'     => 'Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub',
                    'attribute' => new PropertyPath('test_attribute'),
                    'arguments' => [['test', new PropertyPath('test_attribute')]],
                ],
                ['test_attribute' => 'test_value'],
                ['test', 'test_value']
            ],
            'with property data' => [
                'options' => [
                    'class'     => ItemStub::class,
                    'attribute' => new PropertyPath('test_attribute'),
                    'arguments' => [],
                    'data' => new PropertyPath('data_attr'),
                ],
                ['data_attr' => ['key1' => 'val1']],
                ['key1' => 'val1']
            ]
        ];
    }

    protected function getPropertyPath()
    {
        return $this->getMockBuilder(PropertyPath::class)->disableOriginalConstructor()->getMock();
    }
}
