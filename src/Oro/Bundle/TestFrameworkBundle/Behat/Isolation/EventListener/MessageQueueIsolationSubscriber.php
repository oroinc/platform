<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation\EventListener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\SkipIsolatorsTrait;
use Oro\Bundle\TestFrameworkBundle\Behat\Processor\MessageQueueProcessorInterface;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Subscriber that processed message queue during test execution
 */
class MessageQueueIsolationSubscriber implements EventSubscriberInterface
{
    use SkipIsolatorsTrait;

    /** @var KernelInterface */
    private $kernel;

    /** @var MessageQueueProcessorInterface */
    private $dbalMessageQueueProcessor;

    /** @var MessageQueueProcessorInterface */
    private $amqpMessageQueueProcessor;

    /** @var OutputInterface */
    private $output;

    /**
     * @param KernelInterface $kernel
     * @param MessageQueueProcessorInterface $dbalMessageQueueProcessor
     * @param MessageQueueProcessorInterface $amqpMessageQueueProcessor
     */
    public function __construct(
        KernelInterface $kernel,
        MessageQueueProcessorInterface $dbalMessageQueueProcessor,
        MessageQueueProcessorInterface $amqpMessageQueueProcessor
    ) {
        $this->kernel = $kernel;
        $this->dbalMessageQueueProcessor = $dbalMessageQueueProcessor;
        $this->amqpMessageQueueProcessor = $amqpMessageQueueProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeStepTested::BEFORE => ['beforeStep'],
            BeforeFeatureTested::BEFORE => ['beforeFeature'],
            AfterFeatureTested::AFTER => ['afterFeature'],
        ];
    }

    /**
     * @param BeforeStepTested $event
     */
    public function beforeStep(BeforeStepTested $event)
    {
        if ($this->skip) {
            return;
        }

        if (preg_match(OroMainContext::SKIP_WAIT_PATTERN, $event->getStep()->getText())) {
            // Don't wait when we need assert the flash message, because it can disappear until ajax in process
            return;
        }

        $this->getMessageQueueProcessor()->waitWhileProcessingMessages();
    }

    public function beforeFeature()
    {
        if ($this->skip) {
            return;
        }

        $this->output->writeln('<info>Start message queue</info>');
        $this->getMessageQueueProcessor()->startMessageQueue();
    }

    public function afterFeature()
    {
        if ($this->skip) {
            return;
        }

        $this->output->writeln('<info>Stop message queue</info>');
        $this->getMessageQueueProcessor()->stopMessageQueue();

        $this->output->writeln('<info>Clenup message queue</info>');
        $this->getMessageQueueProcessor()->cleanUp();
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @return MessageQueueProcessorInterface
     */
    private function getMessageQueueProcessor()
    {
        $container = $this->kernel->getContainer();
        if ($container->getParameter('message_queue_transport') === 'amqp') {
            return $this->amqpMessageQueueProcessor;
        }

        return $this->dbalMessageQueueProcessor;
    }
}
