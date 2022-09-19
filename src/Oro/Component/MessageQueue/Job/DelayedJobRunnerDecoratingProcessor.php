<?php

namespace Oro\Component\MessageQueue\Job;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\JobRedeliveryException;
use Oro\Component\MessageQueue\Exception\JobRuntimeException;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Wraps a message processor into a delayed job callback.
 */
class DelayedJobRunnerDecoratingProcessor implements MessageProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const JOB_ID = 'jobId';

    private JobRunner $jobRunner;

    private MessageProcessorInterface $decoratedProcessor;

    public function __construct(JobRunner $jobRunner, MessageProcessorInterface $decoratedProcessor)
    {
        $this->jobRunner = $jobRunner;
        $this->decoratedProcessor = $decoratedProcessor;

        $this->logger = new NullLogger();
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $message->getBody();
        if (!isset($messageBody[self::JOB_ID])) {
            $this->logger->error(
                'Rejecting the message {messageId} because jobId option is missing from the message body',
                ['messageId' => $message->getMessageId()]
            );

            return self::REJECT;
        }

        $decoratedProcessorMessage = clone $message;
        $jobId = $messageBody[self::JOB_ID];
        unset($messageBody[self::JOB_ID]);
        $decoratedProcessorMessage->setBody($messageBody);

        try {
            $result = $this->jobRunner->runDelayed(
                $jobId,
                function () use ($decoratedProcessorMessage, $session, &$status) {
                    $status = $this->decoratedProcessor->process($decoratedProcessorMessage, $session);
                    if ($status === self::REQUEUE) {
                        throw JobRedeliveryException::create();
                    }

                    return $status === true || $status === self::ACK;
                }
            );
            $status = $result ? self::ACK : self::REJECT;
        } catch (JobRuntimeException|JobRedeliveryException $exception) {
            // Delayed job that is interrupted by an exception is always marked for redelivery, so the message should
            // be re-queued.
            $status = self::REQUEUE;
        }

        return $status;
    }
}
