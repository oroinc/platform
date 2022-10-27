<?php

namespace Oro\Bundle\ReminderBundle\Model;

use Oro\Bundle\ReminderBundle\Exception\MethodNotSupportedException;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The registry of reminder send processors.
 */
class SendProcessorRegistry implements ResetInterface
{
    /** @var string[] */
    private $sendMethods;

    /** @var ContainerInterface */
    private $processorContainer;

    /** @var SendProcessorInterface[]|null [method => processor, ...] */
    private $processors;

    /**
     * @param string[]           $sendMethods
     * @param ContainerInterface $processorContainer
     */
    public function __construct(array $sendMethods, ContainerInterface $processorContainer)
    {
        $this->sendMethods = $sendMethods;
        $this->processorContainer = $processorContainer;
    }

    /**
     * Gets all processors.
     *
     * @return SendProcessorInterface[] [method => processor, ...]
     */
    public function getProcessors(): array
    {
        if (null === $this->processors) {
            $this->processors = [];
            foreach ($this->sendMethods as $method) {
                $this->processors[$method] = $this->processorContainer->get($method);
            }
        }

        return $this->processors;
    }

    /**
     * Gets a processor for the given send method.
     *
     * @throws MethodNotSupportedException if the given send method is not supported
     */
    public function getProcessor(string $method): SendProcessorInterface
    {
        if (!\in_array($method, $this->sendMethods, true)) {
            throw new MethodNotSupportedException(sprintf('Reminder method "%s" is not supported.', $method));
        }

        return $this->processorContainer->get($method);
    }

    /**
     * {@inheritDoc}
     */
    public function reset()
    {
        $this->processors = null;
    }
}
