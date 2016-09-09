<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\Action\Action\CloneObject;
use Oro\Component\Action\Model\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;

class CloneObjectTest extends \PHPUnit_Framework_TestCase
{
    /** @var CloneObject */
    protected $action;

    /** @var ContextAccessor */
    protected $contextAccessor;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();
        $this->action = new CloneObject($this->contextAccessor);

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
     * @expectedExceptionMessage Target parameter is required
     */
    public function testInitializeExceptionNoTargetObject()
    {
        $this->action->initialize(['some' => 1, 'attribute' => $this->getPropertyPath()]);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Attribute name parameter is required.
     */
    public function testInitializeExceptionNoAttribute()
    {
        $this->action->initialize(['target' => new \stdClass()]);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Attribute must be valid property definition.
     */
    public function testInitializeExceptionInvalidAttribute()
    {
        $this->action->initialize(['target' => new \stdClass(), 'attribute' => 'string']);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Object data must be an array.
     */
    public function testInitializeExceptionInvalidData()
    {
        $this->action->initialize(
            ['target' => new \stdClass(), 'attribute' => $this->getPropertyPath(), 'data' => 'string_value']
        );
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Ignored properties should be a sequence.
     */
    public function testInitializeExceptionInvalidIgnoredProperties()
    {
        $this->action->initialize(
            ['target' => new \stdClass(), 'attribute' => $this->getPropertyPath(), 'ignore' => 'string_value']
        );
    }

    public function testInitialize()
    {
        $options = ['target' => new \stdClass(), 'attribute' => $this->getPropertyPath()];
        $this->assertEquals($this->action, $this->action->initialize($options));
        $this->assertAttributeEquals($options, 'options', $this->action);
    }

    protected function getPropertyPath()
    {
        return $this->getMockBuilder('Symfony\Component\PropertyAccess\PropertyPath')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider executeDataProvider
     * @param array $options
     * @param array $expectedData
     * @param null|array $contextData
     */
    public function testExecute(array $options, array $contextData = [], array $expectedData = null)
    {
        $context = new ItemStub($contextData);
        $attributeName = (string)$options['attribute'];
        $this->action->initialize($options);
        $this->action->execute($context);
        $this->assertNotNull($context->$attributeName);
        $this->assertInstanceOf(get_class($options['target']), $context->$attributeName);

        if ($context->$attributeName instanceof ItemStub) {
            /** @var ItemStub $entity */
            $entity = $context->$attributeName;
            if (!$expectedData) {
                $expectedData = !empty($options['data']) ? $options['data'] : [];
            }
            $this->assertInstanceOf(get_class($options['target']), $entity);
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
