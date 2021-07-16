<?php

namespace Oro\Bundle\MessageQueueBundle\Client;

use Psr\Container\ContainerInterface;

/**
 * Delegates the filtering of messages to child filters.
 * The child filter instances are instantiated only when the message buffer contains topics
 * for which filters are registered.
 */
class ChainMessageFilter implements MessageFilterInterface
{
    /** @var array [[filter service id, topic or NULL], ...] */
    private $filters;

    /** @var ContainerInterface */
    private $container;

    public function __construct(array $filters, ContainerInterface $container)
    {
        $this->filters = $filters;
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(MessageBuffer $buffer): void
    {
        $filters = $this->getFilters($buffer);
        foreach ($filters as $filter) {
            $filter->apply($buffer);
        }
    }

    /**
     * @param MessageBuffer $buffer
     *
     * @return MessageFilterInterface[]
     */
    private function getFilters(MessageBuffer $buffer): array
    {
        $filters = [];
        $topics = $buffer->getTopics();
        foreach ($this->filters as [$serviceId, $topic]) {
            if (!$topic || \in_array($topic, $topics, true)) {
                $filters[] = $this->container->get($serviceId);
            }
        }

        return $filters;
    }
}
