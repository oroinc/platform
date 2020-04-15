<?php

namespace Oro\Bundle\ApiBundle\Batch;

use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * Provides a set of utility methods to get names of different files used when processing batch operations.
 */
class FileNameProvider
{
    /**
     * @return string
     */
    public function getDataFileName(): string
    {
        return sprintf('api_%s', UUIDGenerator::v4());
    }

    /**
     * @param int $operationId
     *
     * @return string
     */
    public function getFilePrefix(int $operationId): string
    {
        return sprintf('api_%d_', $operationId);
    }

    /**
     * @param int $operationId
     *
     * @return string
     */
    public function getInfoFileName(int $operationId): string
    {
        return sprintf('api_%d_info', $operationId);
    }

    /**
     * @param int $operationId
     *
     * @return string
     */
    public function getChunkIndexFileName(int $operationId): string
    {
        return sprintf('api_%d_chunk_index', $operationId);
    }

    /**
     * @param int $operationId
     *
     * @return string
     */
    public function getChunkJobIndexFileName(int $operationId): string
    {
        return sprintf('api_%d_chunk_job_index', $operationId);
    }

    /**
     * @param int $operationId
     *
     * @return string
     */
    public function getChunkFileNameTemplate(int $operationId): string
    {
        return sprintf('api_%d_chunk_', $operationId) . '%s';
    }

    /**
     * @param string $chunkFileName
     *
     * @return string
     */
    public function getChunkErrorsFileName(string $chunkFileName): string
    {
        return $chunkFileName . '_errors';
    }

    /**
     * @param int $operationId
     *
     * @return string
     */
    public function getErrorIndexFileName(int $operationId): string
    {
        return sprintf('api_%d_error_index', $operationId);
    }

    /**
     * @param int $operationId
     *
     * @return string
     */
    public function getIncludeIndexFileName(int $operationId): string
    {
        return sprintf('api_%d_include_index', $operationId);
    }

    /**
     * @param int $operationId
     *
     * @return string
     */
    public function getProcessedIncludeIndexFileName(int $operationId): string
    {
        return sprintf('api_%d_include_index_processed', $operationId);
    }

    /**
     * @param int $operationId
     *
     * @return string
     */
    public function getLinkedIncludeIndexFileName(int $operationId): string
    {
        return sprintf('api_%d_include_index_linked', $operationId);
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    public function getLockFileName(string $fileName): string
    {
        return $fileName . '.lock';
    }
}
