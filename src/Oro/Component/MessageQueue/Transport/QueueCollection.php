<?php

namespace Oro\Component\MessageQueue\Transport;

/**
 * A collection that can be used to store queues.
 */
class QueueCollection
{
    /** @var QueueInterface[] [queue name => QueueInterface, ...] */
    private $queues = [];

    /**
     * Checks whether an queue with the given name is contained in the collection.
     *
     * @param string $queueName
     *
     * @return bool
     */
    public function has($queueName)
    {
        return isset($this->queues[$queueName]);
    }

    /**
     * Gets the queue by its name.
     *
     * @param string $queueName
     *
     * @return QueueInterface
     */
    public function get($queueName)
    {
        if (!isset($this->queues[$queueName])) {
            throw new \OutOfBoundsException(sprintf('The collection does not contain the queue "%s".', $queueName));
        }

        return $this->queues[$queueName];
    }

    /**
     * Adds the queue to the collection.
     * If a queue with the given name already exist in the collection it will be replaces with the new one.
     *
     * @param string         $queueName
     * @param QueueInterface $queue
     */
    public function set($queueName, QueueInterface $queue)
    {
        $this->queues[$queueName] = $queue;
    }

    /**
     * Removes the queue from the collection.
     *
     * @param string $queueName
     *
     * @return QueueInterface|null The removed queue or NULL,
     *                             if the collection did not contain a queue with the given name.
     */
    public function remove($queueName)
    {
        $queue = null;
        if (isset($this->queues[$queueName])) {
            $queue = $this->queues[$queueName];
        }
        unset($this->queues[$queueName]);

        return $queue;
    }

    /**
     * Clears the collection, removing all elements.
     */
    public function clear()
    {
        $this->queues = [];
    }

    /**
     * Checks whether the collection is empty (contains no elements).
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->queues);
    }

    /**
     * Gets all queues stored in the collection.
     *
     * @return array [queue name => QueueInterface, ...]
     */
    public function all()
    {
        return $this->queues;
    }
}
