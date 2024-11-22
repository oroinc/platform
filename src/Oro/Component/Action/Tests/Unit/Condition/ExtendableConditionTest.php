<?php

namespace Oro\Component\Action\Tests\Unit\Condition;

use Oro\Component\Action\Condition\ExtendableCondition;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Action\Event\ExtendableEventData;
use Oro\Component\Action\Model\ActionDataStorageAwareInterface;
use Oro\Component\Action\Model\ExtendableConditionEventErrorsProcessorInterface;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExtendableConditionTest extends \PHPUnit\Framework\TestCase
{
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private ExtendableConditionEventErrorsProcessorInterface|MockObject $errorsProcessor;
    private ExtendableCondition $extendableCondition;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->errorsProcessor = $this->createMock(ExtendableConditionEventErrorsProcessorInterface::class);

        $this->extendableCondition = new ExtendableCondition(
            $this->eventDispatcher,
            $this->createMock(RequestStack::class),
            $this->createMock(TranslatorInterface::class)
        );
        $this->extendableCondition->setErrorsProcessor($this->errorsProcessor);
        $this->extendableCondition->setContextAccessor(new ContextAccessor());
    }

    public function testIsConditionAllowedIsTrueIfNoEvents(): void
    {
        $options = ['events' => []];
        $this->extendableCondition->initialize($options);

        $this->assertTrue($this->extendableCondition->isConditionAllowed([]));
    }

    public function testIsConditionAllowedIsTrueIfNoEventListeners(): void
    {
        $options = ['events' => ['aaa']];
        $this->extendableCondition->initialize($options);
        $this->eventDispatcher->expects($this->once())
            ->method('hasListeners')
            ->willReturn(false);

        $this->assertTrue($this->extendableCondition->isConditionAllowed([]));
    }

    private function expectsDispatchWithErrors(array $options): void
    {
        $this->extendableCondition->initialize($options);
        $this->eventDispatcher->expects($this->once())
            ->method('hasListeners')
            ->willReturn(true);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (ExtendableConditionEvent $event) {
                $event->addError('First error');
                $event->addError('Second error');

                return $event;
            });
    }

    /**
     * @dataProvider notDisplayingErrorsDataProvider
     */
    public function testIsConditionAllowedWhenNotDisplayingErrors(array $context, array $options): void
    {
        $this->expectsDispatchWithErrors(array_merge(['events' => ['aaa']], $options));

        $this->errorsProcessor->expects($this->once())
            ->method('processErrors');

        $this->assertFalse($this->extendableCondition->isConditionAllowed($context));
    }

    public function notDisplayingErrorsDataProvider(): array
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

    public function testIsConditionAllowedEventDataInContext(): void
    {
        $data = ['key' => 'value'];
        $options = [
            'events' => ['test_event'],
            'eventData' => $data
        ];
        $context = new ExtendableEventData([]);

        $this->eventDispatcher->expects($this->once())
            ->method('hasListeners')
            ->willReturn(true);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new ExtendableConditionEvent(new ExtendableEventData($data)), 'test_event');

        $this->extendableCondition->initialize($options);
        $this->assertTrue($this->extendableCondition->isConditionAllowed($context));
    }

    public function testIsConditionAllowedWithActionDataStorageAwareInterface(): void
    {
        $options = ['events' => ['test_event']];
        $context = $this->createMock(ActionDataStorageAwareInterface::class);
        $context->expects($this->never())
            ->method('getActionDataStorage');

        $this->eventDispatcher->expects($this->once())
            ->method('hasListeners')
            ->willReturn(true);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new ExtendableConditionEvent($context), 'test_event');

        $this->extendableCondition->initialize($options);
        $this->assertTrue($this->extendableCondition->isConditionAllowed($context));
    }

    public function testIsConditionAllowedWithAbstractStorage(): void
    {
        $options = ['events' => ['test_event']];
        $context = new ExtendableEventData(['key' => 'value']);

        $this->eventDispatcher->expects($this->once())
            ->method('hasListeners')
            ->willReturn(true);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new ExtendableConditionEvent($context), 'test_event');

        $this->extendableCondition->initialize($options);
        $this->assertTrue($this->extendableCondition->isConditionAllowed($context));
    }

    /**
     * @dataProvider displayingErrorsDataProvider
     */
    public function testIsConditionAllowedWhenDisplayingErrors(
        array $context,
        array $options,
        string $expectedMessageType
    ): void {
        $this->expectsDispatchWithErrors(array_merge(['events' => ['aaa']], $options));

        $this->errorsProcessor->expects($this->once())
            ->method('processErrors')
            ->willReturnCallback(
                function ($event, $showErrors, $errorsCollection, $messageType) use ($expectedMessageType) {
                    self::assertInstanceOf(ExtendableConditionEvent::class, $event);
                    self::assertEquals(
                        [
                            ['message' => 'First error', 'context' => null],
                            ['message' => 'Second error', 'context' => null],
                        ],
                        $event->getErrors()->toArray()
                    );
                    self::assertTrue($showErrors);
                    self::assertEquals($expectedMessageType, $messageType);

                    return [];
                }
            );

        $this->assertFalse($this->extendableCondition->isConditionAllowed($context));
    }

    public function displayingErrorsDataProvider(): array
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

    public function testInitializeThrowsExceptionIfNoEventsSpecified(): void
    {
        $options = [];
        $this->expectException(MissingOptionsException::class);
        $this->extendableCondition->initialize($options);
    }

    public function testInitializeNotThrowsException(): void
    {
        $options = ['events' => ['aaa', 'bbb']];
        $this->extendableCondition->initialize($options);
    }
}
