<?php

namespace Oro\Component\Action\Tests\Unit\Condition;

use Oro\Component\Action\Condition\ExtendableCondition;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Translation\TranslatorInterface;

class ExtendableConditionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $eventDispatcher;

    /**
     * @var FlashBag|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $flashBag;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var ExtendableCondition
     */
    protected $extendableCondition;

    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->flashBag = $this->createMock(FlashBag::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->extendableCondition = new ExtendableCondition(
            $this->eventDispatcher,
            $this->flashBag,
            $this->translator
        );
        $this->extendableCondition->setContextAccessor(new ContextAccessor());
    }

    public function testIsConditionAllowedIsTrueIfNoEvents()
    {
        $options = ['events' => []];
        $this->extendableCondition->initialize($options);

        $this->assertTrue($this->extendableCondition->isConditionAllowed([]));
    }

    public function testIsConditionAllowedIsTrueIfNoEventListeners()
    {
        $options = ['events' => ['aaa']];
        $this->extendableCondition->initialize($options);
        $this->eventDispatcher->expects($this->once())
            ->method('hasListeners')
            ->willReturn(false);

        $this->assertTrue($this->extendableCondition->isConditionAllowed([]));
    }

    /**
     * @param array $options
     */
    private function expectsDispatchWithErrors(array $options)
    {
        $this->extendableCondition->initialize($options);
        $this->eventDispatcher->expects($this->once())
            ->method('hasListeners')
            ->willReturn(true);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function ($eventName, ExtendableConditionEvent $event) {
                    $event->addError('First error');
                    $event->addError('Second error');
                }
            );
    }

    /**
     * @dataProvider notDisplayingErrorsDataProvider
     * @param array $context
     * @param array $options
     */
    public function testIsConditionAllowedWhenNotDisplayingErrors(array $context, array $options)
    {
        $this->expectsDispatchWithErrors(array_merge(['events' => ['aaa']], $options));

        $this->translator
            ->expects($this->exactly(2))
            ->method('trans');
        $this->flashBag
            ->expects($this->never())
            ->method('add');

        $this->assertFalse($this->extendableCondition->isConditionAllowed($context));
    }

    /**
     * @return array
     */
    public function notDisplayingErrorsDataProvider()
    {
        return [
            'show errors is false by default' => [
                'context' => [],
                'options' => []
            ],
            'show errors is false' => [
                'context' => [],
                'options' => [
                    'showErrors' => false
                ]
            ],
            'show errors is false from context' => [
                'context' => [
                    'contextShowErrorsParameter' => false
                ],
                'options' => [
                    'showErrors' => new PropertyPath('contextShowErrorsParameter')
                ]
            ],
        ];
    }

    /**
     * @dataProvider displayingErrorsDataProvider
     * @param array $context
     * @param array $options
     * @param string $expectedMessageType
     */
    public function testIsConditionAllowedWhenDisplayingErrors(array $context, array $options, $expectedMessageType)
    {
        $this->expectsDispatchWithErrors(array_merge(['events' => ['aaa']], $options));

        $this->translator
            ->expects($this->exactly(2))
            ->method('trans')
            ->withConsecutive(['First error'], ['Second error'])
            ->willReturnOnConsecutiveCalls('Translated first error', 'Translated second error');
        $this->flashBag
            ->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [$expectedMessageType, 'Translated first error'],
                [$expectedMessageType, 'Translated second error']
            );

        $this->assertFalse($this->extendableCondition->isConditionAllowed($context));
    }

    /**
     * @return array
     */
    public function displayingErrorsDataProvider()
    {
        return [
            'show messages with default message type when no message type given' => [
                'context' => [],
                'options' => [
                    'showErrors' => true,
                ],
                'expectedMessageType' => 'error'
            ],
            'show message with a given message type' => [
                'context' => [],
                'options' => [
                    'showErrors' => true,
                    'messageType' => 'info'
                ],
                'expectedMessageType' => 'info'
            ],
            'show message with a given message type when show errors options is of PropertyPath type' => [
                'context' => [
                    'contextShowErrorsParameter' => true
                ],
                'options' => [
                    'showErrors' => new PropertyPath('contextShowErrorsParameter'),
                    'messageType' => 'info'
                ],
                'expectedMessageType' => 'info'
            ],
        ];
    }

    public function testInitializeThrowsExceptionIfNoEventsSpecified()
    {
        $options = [];
        $this->expectException(MissingOptionsException::class);
        $this->extendableCondition->initialize($options);
    }

    public function testInitializeNotThrowsException()
    {
        $options = ['events' => ['aaa', 'bbb']];
        $this->extendableCondition->initialize($options);
    }
}
