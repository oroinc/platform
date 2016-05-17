<?php
namespace Oro\Component\Messaging\Consumption;

use Oro\Component\Messaging\Transport\Session;

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
     *
     * @throws \Exception
     *
     * @return void
     */
    public function consume($queueName, MessageProcessor $messageProcessor)
    {
        $queue = $this->session->createQueue($queueName);
        $messageConsumer = $this->session->createConsumer($queue);
        
        $this->extensions->onStart(new Context($this->session, $messageConsumer, $messageProcessor));

        while (true) {
            $context = new Context($this->session, $messageConsumer, $messageProcessor);
            try {

                if ($message = $messageConsumer->receive($timeout = 100)) {
                    $context->setMessage($message);

                    $this->extensions->onPreReceived($context);
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

                    $this->extensions->onPostReceived($context);
                } else {
                    $this->extensions->onIdle($context);
                }

                if ($context->isExecutionInterrupted()) {
                    $this->extensions->onInterrupted($context);

                    return;
                }
            } catch (\Exception $e) {
                $context->setExecutionInterrupted(true);
                $context->setException($e);
                $this->extensions->onInterrupted($context);
                
                throw $e;
            }
        }
    }
}
