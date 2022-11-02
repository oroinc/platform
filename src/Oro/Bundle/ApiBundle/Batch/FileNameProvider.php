<?php

namespace Oro\Bundle\ApiBundle\Batch;

use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * Provides a set of utility methods to get names of different files used when processing batch operations.
 */
class FileNameProvider
{
    public function getDataFileName(): string
    {
        return sprintf('api_%s', UUIDGenerator::v4());
    }

    public function getFilePrefix(int $operationId): string
    {
        return sprintf('api_%d_', $operationId);
    }

    public function getInfoFileName(int $operationId): string
    {
        return sprintf('api_%d_info', $operationId);
    }

    public function getChunkIndexFileName(int $operationId): string
    {
        return sprintf('api_%d_chunk_index', $operationId);
    }

    public function getChunkJobIndexFileName(int $operationId): string
    {
        return sprintf('api_%d_chunk_job_index', $operationId);
    }

    public function getChunkFileNameTemplate(int $operationId): string
    {
        return sprintf('api_%d_chunk_', $operationId) . '%s';
    }

    public function getChunkErrorsFileName(string $chunkFileName): string
    {
        return $chunkFileName . '_errors';
    }

    public function getErrorIndexFileName(int $operationId): string
    {
        return sprintf('api_%d_error_index', $operationId);
    }

    public function getIncludeIndexFileName(int $operationId): string
    {
        return sprintf('api_%d_include_index', $operationId);
    }

    public function getProcessedIncludeIndexFileName(int $operationId): string
    {
        return sprintf('api_%d_include_index_processed', $operationId);
    }

    public function getLinkedIncludeIndexFileName(int $operationId): string
    {
        return sprintf('api_%d_include_index_linked', $operationId);
    }

    public function getLockFileName(string $fileName): string
    {
        return $fileName . '.lock';
    }
}
