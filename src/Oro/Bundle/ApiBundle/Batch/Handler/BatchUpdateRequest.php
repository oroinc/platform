<?php

namespace Oro\Bundle\ApiBundle\Batch\Handler;

use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\GaufretteBundle\FileManager;

/**
 * Represents a request for API batch update operation.
 */
class BatchUpdateRequest
{
    private string $version;
    private RequestType $requestType;
    private int $operationId;
    /** @var string[] */
    private array $supportedEntityClasses;
    private ChunkFile $file;
    private FileManager $fileManager;

    /**
     * @param string      $version                The API version
     * @param RequestType $requestType            The request type, for example "rest", "soap", etc.
     * @param int         $operationId            The asynchronous operation ID this batch operation is processed within
     * @param string[]    $supportedEntityClasses The entity classes supported by this batch operation.
     * @param ChunkFile   $file                   The information about the file contains the input data
     * @param FileManager $fileManager            The manager responsible to work with input and output files
     *                                            related to this batch operation
     */
    public function __construct(
        string $version,
        RequestType $requestType,
        int $operationId,
        array $supportedEntityClasses,
        ChunkFile $file,
        FileManager $fileManager
    ) {
        $this->version = $version;
        $this->requestType = $requestType;
        $this->operationId = $operationId;
        $this->supportedEntityClasses = $supportedEntityClasses;
        $this->file = $file;
        $this->fileManager = $fileManager;
    }

    /**
     * Gets the API version.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Gets the request type, for example "rest", "soap", etc.
     */
    public function getRequestType(): RequestType
    {
        return $this->requestType;
    }

    /**
     * Gets an identifier of an asynchronous operation this batch operation is processed within.
     */
    public function getOperationId(): int
    {
        return $this->operationId;
    }

    /**
     * Gets entity classes supported by this batch operation.
     *
     * @return string[] The list of supported entity classes.
     *                  or empty array if any entities can be processed by this batch operation.
     */
    public function getSupportedEntityClasses(): array
    {
        return $this->supportedEntityClasses;
    }

    /**
     * Gets the information about the file contains the input data.
     */
    public function getFile(): ChunkFile
    {
        return $this->file;
    }

    /**
     * Gets the manager responsible to work with input and output files
     * related to this batch operation.
     */
    public function getFileManager(): FileManager
    {
        return $this->fileManager;
    }
}
