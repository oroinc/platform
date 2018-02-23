<?php

namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Exception\LogicException;
use Psr\Log\LoggerInterface;

class SignalExtension extends AbstractExtension
{
    /** @var bool */
    protected $interruptConsumption = false;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    public function onStart(Context $context)
    {
        if (!extension_loaded('pcntl')) {
            throw new LogicException('The pcntl extension is required in order to catch signals.');
        }

        $this->logger = $context->getLogger();

        pcntl_signal(SIGTERM, [$this, 'handleSignal']);
        pcntl_signal(SIGQUIT, [$this, 'handleSignal']);
        pcntl_signal(SIGINT, [$this, 'handleSignal']);

        $this->interruptConsumption = false;
    }

    /**
     * @param Context $context
     */
    public function onBeforeReceive(Context $context)
    {
        pcntl_signal_dispatch();

        $this->interruptExecutionIfNeeded($context);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        pcntl_signal_dispatch();

        $this->interruptExecutionIfNeeded($context);
    }

    /**
     * {@inheritdoc}
     */
    public function onIdle(Context $context)
    {
        pcntl_signal_dispatch();

        $this->interruptExecutionIfNeeded($context);
    }

    /**
     * @param Context $context
     */
    protected function interruptExecutionIfNeeded(Context $context)
    {
        if ($this->interruptConsumption && !$context->isExecutionInterrupted()) {
            $this->logger->debug('Interrupt execution');
            $context->setExecutionInterrupted($this->interruptConsumption);
            $context->setInterruptedReason('Interrupt execution.');

            $this->interruptConsumption = false;
        }
    }

    /**
     * @param int $signal
     */
    public function handleSignal($signal)
    {
        if ($this->logger) {
            $this->logger->debug(sprintf('Caught signal: %s', $signal));
        }
        
        switch ($signal) {
            case SIGTERM:  // 15 : supervisor default stop
            case SIGQUIT:  // 3  : kill -s QUIT
            case SIGINT:   // 2  : ctrl+c
                $this->logger->debug('Interrupt consumption');
                $this->interruptConsumption = true;
                break;
            default:
                break;
        }
    }
}
