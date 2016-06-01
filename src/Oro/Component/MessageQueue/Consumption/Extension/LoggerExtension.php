<?php
namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\ExtensionTrait;
use Psr\Log\LoggerInterface;

class LoggerExtension implements ExtensionInterface
{
    use ExtensionTrait;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function onStart(Context $context)
    {
        $context->setLogger($this->logger);
        $context->getLogger()->debug(sprintf('Set context\'s logger %s', get_class($this->logger)));
        $context->getLogger()->info(sprintf(
            'Start consuming from queue %s',
            $context->getMessageConsumer()->getQueue()->getQueueName()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context)
    {
        $context->getLogger()->info('Before receive');
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        $message = $context->getMessage();

        $logger = $context->getLogger();
        $logger->info('Message received');
        $logger->debug(sprintf('Headers: %s', var_export($message->getHeaders(), true)));
        $logger->debug(sprintf('Properties: %s', var_export($message->getProperties(), true)));
        $logger->debug(sprintf('Payload: %s', var_export($message->getBody(), true)));
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        $context->getLogger()->info(sprintf('Message processed: %s', $context->getStatus()));
    }

    /**
     * {@inheritdoc}
     */
    public function onIdle(Context $context)
    {
        $context->getLogger()->info(sprintf('Idle'));
    }

    /**
     * {@inheritdoc}
     */
    public function onInterrupted(Context $context)
    {
        if ($context->getException()) {
            $context->getLogger()->error(sprintf('Consuming interrupted by exception'));
        } else {
            $context->getLogger()->info(sprintf('Consuming interrupted'));
        }
    }
}
