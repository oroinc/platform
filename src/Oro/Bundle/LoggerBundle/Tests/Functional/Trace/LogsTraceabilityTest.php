<?php

namespace Oro\Bundle\LoggerBundle\Tests\Functional\Trace;

use Oro\Bundle\LoggerBundle\Trace\TraceManagerInterface;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Test\Async\Topic\BasicMessageTestTopic;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @dbIsolationPerTest
 */
class LogsTraceabilityTest extends WebTestCase
{
    use MessageQueueExtension;

    private const TRACE_VALIDATION_REGEX = '/^[a-f0-9]{32}$/';
    private const MESSAGE_PROPERTY_TRACE_ID = 'traceId';

    private string $testUrl;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        self::purgeMessageQueue();

        $this->testUrl = $this->getUrl('oro_user_security_login');
    }

    public function testRequestIsolation(): void
    {
        $traceIds = [];

        for ($i = 0; $i < 3; $i++) {
            $this->client->request('GET', $this->testUrl);
            self::assertResponseIsSuccessful();

            $traceId = $this->getTraceStorage()->get();
            self::assertNotNull($traceId);
            self::assertNotContains($traceId, $traceIds, 'Each request should have a unique trace ID');

            $traceIds[] = $traceId;
        }

        self::assertCount(3, array_unique($traceIds));
    }

    public function testMessageQueueStandaloneTrace(): void
    {
        $messageBody = ['test' => 'standalone'];
        $sentMessage = self::sendMessage(BasicMessageTestTopic::getName(), $messageBody);

        $traceId = $sentMessage->getProperty(self::MESSAGE_PROPERTY_TRACE_ID);
        self::assertNotNull($traceId);
        self::assertNotEmpty($traceId);

        self::assertMatchesRegularExpression(self::TRACE_VALIDATION_REGEX, $traceId);
    }

    public function testRequestToMessageQueueTraceFlow(): void
    {
        $traceStorage = $this->getTraceStorage();
        $requestTrace = '77777777777777777777777777777777';

        $this->client->request('GET', $this->testUrl, [], [], ['HTTP_X_REQUEST_ID' => $requestTrace]);
        self::assertResponseIsSuccessful();

        self::assertSame($requestTrace, $traceStorage->get());

        $messageBody = ['test' => 'integration'];
        $sentMessage = self::sendMessage(BasicMessageTestTopic::getName(), new Message($messageBody));

        self::assertSame($requestTrace, $sentMessage->getProperty(self::MESSAGE_PROPERTY_TRACE_ID));
    }

    public function testTraceIdFormatConsistency(): void
    {
        $this->client->request('GET', $this->testUrl);
        $requestTrace = $this->getTraceStorage()->get();
        self::assertMatchesRegularExpression(self::TRACE_VALIDATION_REGEX, $requestTrace);

        $application = new Application(self::getContainer()->get('kernel'));
        $application->setAutoExit(false);
        $application->doRun(new ArrayInput(['command' => 'list']), new BufferedOutput());

        $consoleTrace = $this->getTraceStorage()->get();
        self::assertMatchesRegularExpression(self::TRACE_VALIDATION_REGEX, $consoleTrace);
        self::assertSame($requestTrace, $consoleTrace);

        $sentMessage = self::sendMessage(BasicMessageTestTopic::getName(), ['test' => 'format']);
        $mqTrace = $sentMessage->getProperty(self::MESSAGE_PROPERTY_TRACE_ID);
        self::assertMatchesRegularExpression(self::TRACE_VALIDATION_REGEX, $mqTrace);
        self::assertSame($requestTrace, $mqTrace);
    }

    public function testCustomTraceIdPreservation(): void
    {
        $customTraceId = '77777777777777777777777777777777';
        $this->client->request('GET', $this->testUrl, [], [], ['HTTP_X_REQUEST_ID' => $customTraceId]);
        self::assertSame($customTraceId, $this->getTraceStorage()->get());

        $sentMessage = self::sendMessage(BasicMessageTestTopic::getName(), new Message(['test' => 'custom']));
        $sentMessages[] = $sentMessage;
        self::assertSame($customTraceId, $sentMessage->getProperty(self::MESSAGE_PROPERTY_TRACE_ID));

        $message = new Message(['test' => 'explicit']);
        $message->setProperty(self::MESSAGE_PROPERTY_TRACE_ID, $customTraceId);
        $sentMessages[] = self::sendMessage(BasicMessageTestTopic::getName(), $message);

        foreach ($sentMessages as $msg) {
            self::assertSame($customTraceId, $msg->getProperty(self::MESSAGE_PROPERTY_TRACE_ID));
        }
    }

    public function testMultipleContextsDoNotInterfere(): void
    {
        $firstTraceId = '55555555555555555555555555555555';
        $secondTraceId = '77777777777777777777777777777777';

        // First context
        $this->client->request('GET', $this->testUrl, [], [], ['HTTP_X_REQUEST_ID' => $firstTraceId]);
        $firstMessage = self::sendMessage(BasicMessageTestTopic::getName(), ['context' => 'first']);
        self::assertSame($firstTraceId, $firstMessage->getProperty(self::MESSAGE_PROPERTY_TRACE_ID));

        // Second context
        $this->client->request('GET', $this->testUrl, [], [], ['HTTP_X_REQUEST_ID' => $secondTraceId]);
        $secondMessage = self::sendMessage(BasicMessageTestTopic::getName(), ['context' => 'second']);
        self::assertSame($secondTraceId, $secondMessage->getProperty(self::MESSAGE_PROPERTY_TRACE_ID));

        self::assertNotSame($firstTraceId, $secondTraceId);
    }

    private function getTraceStorage(): TraceManagerInterface
    {
        return self::getContainer()->get('oro_logger.trace.manager');
    }
}
