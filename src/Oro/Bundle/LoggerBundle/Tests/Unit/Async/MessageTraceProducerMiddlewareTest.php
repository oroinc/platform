<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Async;

use Oro\Bundle\LoggerBundle\Async\MessageTraceProducerMiddleware;
use Oro\Bundle\LoggerBundle\Trace\TraceManager;
use Oro\Bundle\LoggerBundle\Trace\TraceManagerInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MessageTraceProducerMiddlewareTest extends TestCase
{
    private const TRACE_VALIDATION_REGEX = '/^[a-f0-9]{32}$/';
    private const MESSAGE_PROPERTY_TRACE_ID = 'traceId';

    private TraceManagerInterface $traceManager;
    private MessageTraceProducerMiddleware $middleware;

    #[\Override]
    protected function setUp(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->traceManager = new TraceManager($dispatcher);
        $this->middleware = new MessageTraceProducerMiddleware($this->traceManager);
    }

    public function testHandleWithExistingTrace(): void
    {
        $expectedTrace = '77777777777777777777777777777777';
        $this->traceManager->set($expectedTrace);

        $message = new Message();
        $this->middleware->handle($message);

        self::assertSame($expectedTrace, $message->getProperty(self::MESSAGE_PROPERTY_TRACE_ID));
        self::assertSame($expectedTrace, $this->traceManager->get());
    }

    public function testHandleWithMessagePropertyTraceId(): void
    {
        $messageTraceId = '55555555555555555555555555555555';

        $message = new Message();
        $message->setProperty(self::MESSAGE_PROPERTY_TRACE_ID, $messageTraceId);
        $this->middleware->handle($message);

        self::assertSame($messageTraceId, $message->getProperty(self::MESSAGE_PROPERTY_TRACE_ID));
        self::assertNull($this->traceManager->get());
    }

    public function testHandleGeneratesNewTraceIdWhenNoneExists(): void
    {
        $message = new Message();
        $this->middleware->handle($message);

        $traceId = $message->getProperty(self::MESSAGE_PROPERTY_TRACE_ID);
        self::assertNotNull($traceId);
        self::assertMatchesRegularExpression(self::TRACE_VALIDATION_REGEX, $traceId);
        self::assertSame($traceId, $this->traceManager->get());
    }

    public function testHandleWithMultipleMessages(): void
    {
        $traceId = '77777777777777777777777777777777';
        $this->traceManager->set($traceId);

        $firstMessage = new Message();
        $this->middleware->handle($firstMessage);
        self::assertSame($traceId, $firstMessage->getProperty(self::MESSAGE_PROPERTY_TRACE_ID));

        $secondMessage = new Message();
        $this->middleware->handle($secondMessage);
        self::assertSame($traceId, $secondMessage->getProperty(self::MESSAGE_PROPERTY_TRACE_ID));

        $thirdMessage = new Message();
        $this->middleware->handle($thirdMessage);
        self::assertSame($traceId, $thirdMessage->getProperty(self::MESSAGE_PROPERTY_TRACE_ID));

        self::assertSame($traceId, $this->traceManager->get());
    }

    public function testHandlePreservesExistingMessageProperties(): void
    {
        $trace = '77777777777777777777777777777777';
        $this->traceManager->set($trace);

        $message = new Message();
        $message->setProperty('customProperty', 'customValue');
        $message->setProperty('anotherProperty', 'anotherValue');

        $this->middleware->handle($message);

        self::assertSame($trace, $message->getProperty(self::MESSAGE_PROPERTY_TRACE_ID));
        self::assertSame('customValue', $message->getProperty('customProperty'));
        self::assertSame('anotherValue', $message->getProperty('anotherProperty'));

        self::assertSame($trace, $this->traceManager->get());
    }

    public function testOnPostReceivedMessageWithTraceId(): void
    {
        $expectedTraceId = '77777777777777777777777777777777';

        $transportMessage = $this->createTransportMessage([self::MESSAGE_PROPERTY_TRACE_ID => $expectedTraceId]);
        $context = $this->createContext($transportMessage);

        $this->middleware->onPostReceived($context);

        $clientMessage = new Message();
        $this->middleware->handle($clientMessage);

        self::assertSame($expectedTraceId, $clientMessage->getProperty(self::MESSAGE_PROPERTY_TRACE_ID));
        self::assertNull($this->traceManager->get());
    }

    public function testOnPostReceivedMessageWithoutTraceId(): void
    {
        $transportMessage = $this->createTransportMessage([]);
        $context = $this->createContext($transportMessage);

        $this->middleware->onPostReceived($context);

        $clientMessage = new Message();
        self::assertNull($this->traceManager->get());

        $this->middleware->handle($clientMessage);

        self::assertNotNull($clientMessage->getProperty(self::MESSAGE_PROPERTY_TRACE_ID));
        self::assertMatchesRegularExpression(
            self::TRACE_VALIDATION_REGEX,
            $clientMessage->getProperty(self::MESSAGE_PROPERTY_TRACE_ID)
        );
    }

    public function testHandleDoesNotOverrideExistingTrace(): void
    {
        $parentTrace = '55555555555555555555555555555555';
        $existingTrace = '77777777777777777777777777777777';

        $transportMessage = $this->createTransportMessage([self::MESSAGE_PROPERTY_TRACE_ID => $parentTrace]);
        $context = $this->createContext($transportMessage);

        $this->middleware->onPostReceived($context);

        $clientMessage = new Message();
        $clientMessage->setProperty(self::MESSAGE_PROPERTY_TRACE_ID, $existingTrace);
        $this->middleware->handle($clientMessage);

        self::assertSame($existingTrace, $clientMessage->getProperty(self::MESSAGE_PROPERTY_TRACE_ID));
        self::assertNull($this->traceManager->get());
    }

    public function testOnPostReceivedResetsTrace(): void
    {
        $firstTrace = '55555555555555555555555555555555';
        $firstTransportMessage = $this->createTransportMessage([self::MESSAGE_PROPERTY_TRACE_ID => $firstTrace]);
        $firstContext = $this->createContext($firstTransportMessage);

        $this->middleware->onPostReceived($firstContext);

        $firstMessage = new Message();
        $this->middleware->handle($firstMessage);
        self::assertSame($firstTrace, $firstMessage->getProperty(self::MESSAGE_PROPERTY_TRACE_ID));

        $secondTrace = '77777777777777777777777777777777';
        $secondTransportMessage = $this->createTransportMessage([self::MESSAGE_PROPERTY_TRACE_ID => $secondTrace]);
        $secondContext = $this->createContext($secondTransportMessage);

        $this->middleware->onPostReceived($secondContext);

        $secondMessage = new Message();
        $this->middleware->handle($secondMessage);
        self::assertSame($secondTrace, $secondMessage->getProperty(self::MESSAGE_PROPERTY_TRACE_ID));
        self::assertNull($this->traceManager->get());
    }

    private function createTransportMessage(array $properties): MessageInterface&MockObject
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getProperty')
            ->willReturnCallback(function (string $name) use ($properties) {
                return $properties[$name] ?? '';
            });

        return $message;
    }

    private function createContext(MessageInterface $message): Context
    {
        $session = $this->createMock(SessionInterface::class);
        $context = new Context($session);
        $context->setMessage($message);

        return $context;
    }
}
