<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Oro\Bundle\ImportExportBundle\Async\SaveImportExportResultProcessor;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\PostHttpImportEvent;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SaveImportExportResultProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    private $eventDispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    /** @var SaveImportExportResultProcessor */
    private $saveExportResultProcessor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->saveExportResultProcessor = new SaveImportExportResultProcessor($this->eventDispatcher, $this->logger);
    }

    public function testSaveExportProcessor(): void
    {
        $this->assertInstanceOf(MessageProcessorInterface::class, $this->saveExportResultProcessor);
        $this->assertInstanceOf(TopicSubscriberInterface::class, $this->saveExportResultProcessor);
    }

    public function testProcessWithValidMessage(): void
    {
        $this->logger
            ->expects($this->never())
            ->method('critical');

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject */
        $session = $this->createMock(SessionInterface::class);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject */
        $message = $this->createMock(MessageInterface::class);

        $options = ['test1' => 'test2'];
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode([
                'jobId' => '1',
                'type' => ProcessorRegistry::TYPE_EXPORT,
                'entity' => 'Acme',
                'options' => $options,
                'notifyEmail' => 'email@email.com',
            ]));

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(Events::POST_HTTP_IMPORT, new PostHttpImportEvent($options));

        $result = $this->saveExportResultProcessor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @param array $parameters
     * @param string $expectedError
     * @dataProvider getProcessWithInvalidMessageDataProvider
     */
    public function testProcessWithInvalidMessage(array $parameters, $expectedError): void
    {
        $this->logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->stringContains($expectedError));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject */
        $session = $this->createMock(SessionInterface::class);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject */
        $message = $this->createMock(MessageInterface::class);

        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode($parameters));

        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $result = $this->saveExportResultProcessor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    /**
     * @return array
     */
    public function getProcessWithInvalidMessageDataProvider()
    {
        return [
            'without jobId' => [
                'parameters' => [
                    'type' => ProcessorRegistry::TYPE_EXPORT,
                    'entity' => '1',
                    'options' => ['test1' => 'test2']
                ],
                'expectedError' => 'Error occurred during save result: The required option "jobId" is missing.'
            ],
            'without entity' => [
                'parameters' => [
                    'jobId' => 1,
                    'type' => ProcessorRegistry::TYPE_EXPORT,
                    'options' => ['test1' => 'test2']
                ],
                'expectedError' => 'Error occurred during save result: The required option "entity" is missing.'
            ],
            'without type' => [
                'parameters' => [
                    'jobId' => 1,
                    'entity' => '1',
                    'options' => ['test1' => 'test2']
                ],
                'expectedError' => 'Error occurred during save result: The required option "type" is missing.'
            ],
            'invalid processor' => [
                'parameters' => [
                    'jobId' => 1,
                    'type' => 'invalid_type',
                    'entity' => '1',
                    'options' => ['test1' => 'test2']
                ],
                'expectedError' => 'The option "type" with value "invalid_type" is invalid. Accepted values are:'
            ],
            'options not array' => [
                'parameters' => [
                    'jobId' => 1,
                    'type' => ProcessorRegistry::TYPE_EXPORT,
                    'entity' => '1',
                    'options' => 1
                ],
                'expectedError' => 'is expected to be of type "array", but is of type'
            ]
        ];
    }
}
