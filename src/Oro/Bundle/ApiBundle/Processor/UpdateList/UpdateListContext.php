<?php

namespace Oro\Bundle\ApiBundle\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;

/**
 * The execution context for processors for "update_list" action.
 */
class UpdateListContext extends Context
{
    /** indicates whether a batch operation should be processed in the synchronous mode */
    private const SYNCHRONOUS_MODE = 'synchronousMode';

    /** indicates whether a batch operation should be processed by the message queue */
    private const PROCESS_BY_MESSAGE_QUEUE = 'processByMessageQueue';

    /** @var resource|array|null */
    private $requestData = null;
    private ?string $targetFileName = null;
    private ?int $operationId = null;

    public function __construct(ConfigProvider $configProvider, MetadataProvider $metadataProvider)
    {
        parent::__construct($configProvider, $metadataProvider);
        $this->set(self::PROCESS_BY_MESSAGE_QUEUE, true);
    }

    /**
     * Gets a resource contains request data.
     *
     * @return resource|array|null
     */
    public function getRequestData()
    {
        return $this->requestData;
    }

    /**
     * Sets a resource contains request data.
     *
     * @param resource|array|null $requestData
     */
    public function setRequestData($requestData): void
    {
        $this->requestData = $requestData;
    }

    /**
     * Gets the name of a file that is used to store request data.
     */
    public function getTargetFileName(): ?string
    {
        return $this->targetFileName;
    }

    /**
     * Sets the name of a file that is used to store request data.
     */
    public function setTargetFileName(?string $targetFileName): void
    {
        $this->targetFileName = $targetFileName;
    }

    /**
     * Gets an identifier of an asynchronous operation.
     */
    public function getOperationId(): ?int
    {
        return $this->operationId;
    }

    /**
     * Sets an identifier of an asynchronous operation.
     */
    public function setOperationId(?int $operationId): void
    {
        $this->operationId = $operationId;
    }

    /**
     * Checks whether a flag indicates whether a batch operation should be processed in the synchronous mode is set.
     */
    public function hasSynchronousMode(): bool
    {
        return $this->has(self::SYNCHRONOUS_MODE);
    }

    /**
     * Indicates whether a batch operation should be processed in the synchronous mode.
     */
    public function isSynchronousMode(): bool
    {
        return (bool)$this->get(self::SYNCHRONOUS_MODE);
    }

    /**
     * Sets a flag indicates whether a batch operation should be processed in the synchronous mode.
     */
    public function setSynchronousMode(?bool $synchronousMode): void
    {
        if (null === $synchronousMode) {
            $this->remove(self::SYNCHRONOUS_MODE);
        } else {
            $this->set(self::SYNCHRONOUS_MODE, $synchronousMode);
        }
    }

    /**
     * Indicates whether a batch operation should be processed by the message queue.
     */
    public function isProcessByMessageQueue(): bool
    {
        return $this->get(self::PROCESS_BY_MESSAGE_QUEUE);
    }

    /**
     * Sets a flag indicates whether a batch operation should be processed by the message queue.
     */
    public function setProcessByMessageQueue(?bool $processByMessageQueue): void
    {
        $this->set(self::PROCESS_BY_MESSAGE_QUEUE, $processByMessageQueue ?? true);
    }
}
