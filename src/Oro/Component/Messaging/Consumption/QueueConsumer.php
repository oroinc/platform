<?php
namespace Oro\Component\Messaging\Consumption;

use Oro\Component\Messaging\Transport\Session;
use Psr\Log\NullLogger;

class QueueConsumer
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var Extensions
     */
    private $extensions;

    /**
     * @param Session $session
     * @param Extensions $extensions
     */
    public function __construct(Session $session, Extensions $extensions)
    {
        $this->session = $session;
        $this->extensions = $extensions;
    }

    /**
     * @param string $queueName
     * @param MessageProcessor $messageProcessor
     * @param Extensions $extensions
     *
     * @throws \Exception
     */
    public function consume($queueName, MessageProcessor $messageProcessor, Extensions $extensions = null)
    {
        $queue = $this->session->createQueue($queueName);
        $messageConsumer = $this->session->createConsumer($queue);

        if ($extensions) {
            $extensions = new Extensions([$this->extensions, $extensions]);
        } else {
            $extensions = $this->extensions;
        }

        $startContext = new Context($this->session, $messageConsumer, $messageProcessor, new NullLogger());
        $extensions->onStart($startContext);

        while (true) {
            $context = new Context($this->session, $messageConsumer, $messageProcessor, $startContext->getLogger());
            try {
                $extensions->onBeforeReceive($context);
                

                if ($message = $messageConsumer->receive($timeout = 1)) {
                    $context->setMessage($message);

                    $extensions->onPreReceived($context);
                    if (false == $context->getStatus()) {
                        $status = $messageProcessor->process($message, $this->session);
                        $status = $status ?: MessageProcessor::ACK;
                        $context->setStatus($status);
                    }

                    if (MessageProcessor::ACK === $context->getStatus()) {
                        $messageConsumer->acknowledge($message);
                    } elseif (MessageProcessor::REJECT === $context->getStatus()) {
                        $messageConsumer->reject($message, false);
                    } elseif (MessageProcessor::REQUEUE === $context->getStatus()) {
                        $messageConsumer->reject($message, true);
                    } else {
                        throw new \LogicException(sprintf(
                            'Processor returned not supported status: %s',
                            $context->getStatus()
                        ));
                    }

                    $extensions->onPostReceived($context);
                } else {
                    $extensions->onIdle($context);
                }

                if ($context->isExecutionInterrupted()) {
                    $extensions->onInterrupted($context);

                    return;
                }
            } catch (\Exception $e) {
                $context->setExecutionInterrupted(true);
                $context->setException($e);
                $extensions->onInterrupted($context);
                
                throw $e;
            }
        }
    }
}
