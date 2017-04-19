<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ActionBundle\Action\DuplicateEntity;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Duplicator\DuplicatorFactory;

class DuplicateEntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var DuplicateEntity
     */
    protected $action;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();
        $this->action = new DuplicateEntity($this->contextAccessor);
        $this->eventDispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->action->setDispatcher($this->eventDispatcher);
        $this->action->setDuplicatorFactory($this->getDuplicateFactory());
    }

    protected function tearDown()
    {
        unset($this->action);
    }

    public function testInitialize()
    {
        $options = [
            DuplicateEntity::OPTION_KEY_TARGET => 'test_value',
            DuplicateEntity::OPTION_KEY_SETTINGS => [],
            DuplicateEntity::OPTION_KEY_ATTRIBUTE => ['copyResult'],
        ];

        $this->assertInstanceOf(
            'Oro\Component\Action\Action\ActionInterface',
            $this->action->initialize($options)
        );

        $this->assertAttributeEquals($options, 'options', $this->action);
    }


    /**
     * @dataProvider initializeExceptionDataProvider
     *
     * @param array $options
     * @param string $exception
     * @param string $exceptionMessage
     */
    public function testInitializeException(array $options, $exception, $exceptionMessage)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider()
    {
        return [
            [
                'options' => [
                    DuplicateEntity::OPTION_KEY_TARGET => ['target'],
                ],
                'expectedException' => 'Oro\Component\Action\Exception\InvalidParameterException',
                'expectedExceptionMessage' => 'Option \'target\' should be string or PropertyPath',
            ],
            [
                'options' => [
                    DuplicateEntity::OPTION_KEY_SETTINGS => 'wrong settings',
                ],
                'expectedException' => 'Oro\Component\Action\Exception\InvalidParameterException',
                'expectedExceptionMessage' => 'Option \'settings\' should be array',
            ],
            [
                'options' => [],
                'expectedException' => 'Oro\Component\Action\Exception\InvalidParameterException',
                'expectedExceptionMessage' => 'Attribute name parameter is required',
            ],
        ];
    }

    public function testExecute()
    {
        $child = new \stdClass();
        $child->name = 'child';
        $target = new \stdClass();
        $target->name = 'parent';
        $target->child = $child;

        $context = new ActionData(['data' => $target]);
        $this->action->initialize([DuplicateEntity::OPTION_KEY_ATTRIBUTE => 'copyResult']);
        $this->action->execute($context);
        /** @var \stdClass $copyObject */
        $copyObject = $context['copyResult'];
        $this->assertNotSame($copyObject, $target);
        $this->assertEquals($copyObject, $target);
        $this->assertSame($copyObject->child, $copyObject->child);
    }

    public function testExecuteWithEntity()
    {
        $target = new \stdClass();

        /** @var ContextAccessor|\PHPUnit_Framework_MockObject_MockObject $contextAccessor */
        $contextAccessor = $this->getMockBuilder(ContextAccessor::class)->disableOriginalConstructor()->getMock();

        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $eventDispatcher $eventDispatcher */
        $eventDispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $action = new DuplicateEntity($contextAccessor);
        $action->setDispatcher($eventDispatcher);
        $action->setDuplicatorFactory($this->getDuplicateFactory());

        $context = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionData')
            ->disableOriginalConstructor()
            ->getMock();

        $options = [
            DuplicateEntity::OPTION_KEY_ENTITY => '$.data',
            DuplicateEntity::OPTION_KEY_ATTRIBUTE => 'copyResult'
        ];
        $contextAccessor->expects($this->once())
            ->method('getValue')
            ->with($context, $options[DuplicateEntity::OPTION_KEY_ENTITY])
            ->willReturn($target);

        $action->initialize($options);
        $action->execute($context);
    }

    /**
     * @return DuplicatorFactory
     */
    protected function getDuplicateFactory()
    {
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\TaggedContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        return new DuplicatorFactory($container);
    }
}
