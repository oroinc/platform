<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\CreateObject;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class CreateObjectTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CreateObject
     */
    protected $action;

    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();
        $this->action = new CreateObject($this->contextAccessor);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    protected function tearDown()
    {
        unset($this->contextAccessor, $this->action);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Class name parameter is required
     */
    public function testInitializeExceptionNoClassName()
    {
        $this->action->initialize(['some' => 1, 'attribute' => $this->getPropertyPath()]);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Attribute name parameter is required
     */
    public function testInitializeExceptionNoAttribute()
    {
        $this->action->initialize(['class' => 'stdClass']);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Attribute must be valid property definition.
     */
    public function testInitializeExceptionInvalidAttribute()
    {
        $this->action->initialize(['class' => 'stdClass', 'attribute' => 'string']);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Object data must be an array.
     */
    public function testExceptionInvalidData()
    {
        $this->action->initialize(
            ['class' => 'stdClass', 'attribute' => $this->getPropertyPath(), 'data' => 'string_value']
        );

        $this->action->execute(new ItemStub());
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Object constructor arguments must be an array.
     */
    public function testInitializeExceptionInvalidArguments()
    {
        $this->action->initialize(
            ['class' => 'stdClass', 'attribute' => $this->getPropertyPath(), 'arguments' => 'string_value']
        );
    }

    public function testInitialize()
    {
        $options = ['class' => 'stdClass', 'attribute' => $this->getPropertyPath()];
        $this->assertEquals($this->action, $this->action->initialize($options));
        $this->assertAttributeEquals($options, 'options', $this->action);
    }

    /**
     * @param array $options
     * @param array $contextData
     * @param null|array $expectedData
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $options, array $contextData = [], array $expectedData = null)
    {
        $context = new ItemStub($contextData);
        $attributeName = (string)$options['attribute'];
        $this->action->initialize($options);
        $this->action->execute($context);
        $this->assertNotNull($context->$attributeName);
        $this->assertInstanceOf($options['class'], $context->$attributeName);

        if ($context->$attributeName instanceof ItemStub) {
            /** @var ItemStub $entity */
            $entity = $context->$attributeName;
            if (!$expectedData) {
                $expectedData = !empty($options['data']) ? $options['data'] : [];
            }
            $this->assertInstanceOf($options['class'], $entity);
            $this->assertEquals($expectedData, $entity->getData());
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
        return $this->getMockBuilder('Symfony\Component\PropertyAccess\PropertyPath')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
