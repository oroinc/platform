<?php
namespace Oro\Component\Messaging\Consumption\Extension;

declare(ticks = 1);

use Oro\Component\Messaging\Consumption\Context;
use Oro\Component\Messaging\Consumption\Exception\LogicException;
use Oro\Component\Messaging\Consumption\Extension;
use Oro\Component\Messaging\Consumption\ExtensionTrait;
use Psr\Log\LoggerInterface;

class SignalExtension implements Extension
{
    use ExtensionTrait;

    /**
     * @var bool
     */
    protected $interruptConsumption = false;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    public function onStart(Context $context)
    {
        if (false == extension_loaded('pcntl')) {
            throw new LogicException('The pcntl extension is required in order to catch signals.');
        }
        
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
        $this->logger = $context->getLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        if ($this->interruptConsumption) {
            $context->setExecutionInterrupted($this->interruptConsumption);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        if ($this->interruptConsumption) {
            $context->setExecutionInterrupted($this->interruptConsumption);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onIdle(Context $context)
    {
        if ($this->interruptConsumption) {
            $context->setExecutionInterrupted($this->interruptConsumption);
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
                $this->interruptConsumption = true;
                break;
            default:
                break;
        }
    }
}
