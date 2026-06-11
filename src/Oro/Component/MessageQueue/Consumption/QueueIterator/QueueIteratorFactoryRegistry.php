<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption\QueueIterator;

use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * Registry that maps consumption modes to corresponding {@see QueueIteratorFactoryInterface} implementations.
 *
 * All factories tagged with `oro_message_queue.consumption.queue_iterator_factory` are collected
 * here via a tagged service locator, keyed by the `consumption_mode` tag attribute.
 */
class QueueIteratorFactoryRegistry
{
    /**
     * @param ServiceProviderInterface<QueueIteratorFactoryInterface> $queueIteratorFactories
     *        A service locator keyed by consumption mode, e.g. 'default' => DefaultQueueIteratorFactory.
     */
    public function __construct(private readonly ServiceProviderInterface $queueIteratorFactories)
    {
    }

    /**
     * Returns the factory registered for the given consumption mode.
     *
     * @throws \LogicException When no factory is registered for the requested consumption mode.
     */
    public function getQueueIteratorFactory(string $consumptionMode): QueueIteratorFactoryInterface
    {
        if (!$this->queueIteratorFactories->has($consumptionMode)) {
            throw new \LogicException(
                sprintf(
                    'No queue iterator factory is registered for the consumption mode "%s". Supported modes: %s.',
                    $consumptionMode,
                    implode(', ', $this->getConsumptionModes()) ?: '(none)',
                )
            );
        }

        return $this->queueIteratorFactories->get($consumptionMode);
    }

    /**
     * Returns a list of all registered consumption modes.
     *
     * Keys are read from the locator's service map without instantiating any factory.
     *
     * @return array<string>
     */
    public function getConsumptionModes(): array
    {
        return array_keys($this->queueIteratorFactories->getProvidedServices());
    }
}
