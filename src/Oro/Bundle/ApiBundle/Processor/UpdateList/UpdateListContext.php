<?php

namespace Oro\Bundle\ApiBundle\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * The execution context for processors for "update_list" action.
 */
class UpdateListContext extends Context
{
    private ?string $targetFileName = null;
    private ?int $operationId = null;
    /** @var resource|null */
    private $requestData = null;

    /**
     * Gets a resource contains request data.
     *
     * @return resource|null
     */
    public function getRequestData()
    {
        return $this->requestData;
    }

    /**
     * Sets a resource contains request data.
     *
     * @param resource|null $requestData
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
}
