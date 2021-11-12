<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\CloneObject;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class CloneObjectTest extends \PHPUnit\Framework\TestCase
{
    /** @var CloneObject */
    private $action;

    protected function setUp(): void
    {
        $this->action = new CloneObject(new ContextAccessor());
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    public function testInitializeExceptionNoTargetObject()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Target parameter is required');

        $this->action->initialize(['some' => 1, 'attribute' => $this->createMock(PropertyPath::class)]);
    }

    public function testInitializeExceptionNoAttribute()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute name parameter is required.');

        $this->action->initialize(['target' => new \stdClass()]);
    }

    public function testInitializeExceptionInvalidAttribute()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute must be valid property definition.');

        $this->action->initialize(['target' => new \stdClass(), 'attribute' => 'string']);
    }

    public function testInitializeExceptionInvalidData()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Object data must be an array.');

        $this->action->initialize([
            'target' => new \stdClass(),
            'attribute' => $this->createMock(PropertyPath::class),
            'data' => 'string_value'
        ]);
    }

    public function testInitializeExceptionInvalidIgnoredProperties()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Ignored properties should be a sequence.');

        $this->action->initialize([
            'target' => new \stdClass(),
            'attribute' => $this->createMock(PropertyPath::class),
            'ignore' => 'string_value'
        ]);
    }

    public function testInitialize()
    {
        $options = ['target' => new \stdClass(), 'attribute' => $this->createMock(PropertyPath::class)];
        self::assertEquals($this->action, $this->action->initialize($options));
        self::assertEquals($options, ReflectionUtil::getPropertyValue($this->action, 'options'));
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
        self::assertNotNull($context->{$attributeName});
        self::assertInstanceOf(get_class($options['target']), $context->{$attributeName});

        if ($context->{$attributeName} instanceof ItemStub) {
            /** @var ItemStub $entity */
            $entity = $context->{$attributeName};
            if (!$expectedData) {
                $expectedData = !empty($options['data']) ? $options['data'] : [];
            }
            self::assertInstanceOf(get_class($options['target']), $entity);
            self::assertEquals($expectedData, $entity->getData());
        }
    }

    public function executeDataProvider(): array
    {
        return [
            'without data' => [
                'options' => [
                    'target'     => new ItemStub(),
                    'attribute' => new PropertyPath('test_attribute'),
                ],
                [
                    'test_attribute' => 'test_value',
                ]
            ],
            'with data' => [
                'options' => [
                    'target'     => new ItemStub(['key1' => 'to be changed', 'key2' => 'to be changed']),
                    'attribute' => new PropertyPath('test_attribute'),
                    'data'      => ['key1' => new PropertyPath('test_attribute'), 'key2' => 'value2'],
                ],
                [
                    'test_attribute' => 'test_value'
                ],
                ['key1' => 'test_value', 'key2' => 'value2']
            ],
            'with ignore' => [
                'options' => [
                    'target'     => new ItemStub(['key1' => 'to be ignored', 'key2' => 'to be copied']),
                    'attribute' => new PropertyPath('test_attribute'),
                    'ignore' => ['key1'],
                ],
                [
                    'test_attribute' => 'test_value',
                ],
                ['key1' => null, 'key2' => 'to be copied']
            ]
        ];
    }
}
