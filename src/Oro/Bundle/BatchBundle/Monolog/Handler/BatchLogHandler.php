<?php

namespace Oro\Bundle\BatchBundle\Monolog\Handler;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Utils;

/**
 * Writes the log into a separate log file
 */
class BatchLogHandler extends StreamHandler
{
    private string $logDir;

    private bool $isActive;

    public function __construct(string $logDir, $isActive)
    {
        $this->logDir = $logDir;
        $this->isActive = (bool) $isActive;

        $this->filePermission = null;
        $this->useLocking = false;
        $this->bubble = true;

        $this->setupStringChunkSize();
        $this->setLevel(Logger::DEBUG);
    }

    /**
     * Get the filename of the log file
     */
    public function getFilename(): string
    {
        return (string) $this->url;
    }

    public function setSubDirectory(string $subDirectory): void
    {
        $this->url = $this->getRealPath($this->generateLogFilename(), $subDirectory);
    }

    /**
     * Get the real path of the log file
     */
    public function getRealPath(string $filename, ?string $subDirectory = null): string
    {
        if ($subDirectory) {
            return sprintf('%s/%s/%s', $this->logDir, $subDirectory, $filename);
        }

        return sprintf('%s/%s', $this->logDir, $filename);
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $record): void
    {
        if (!$this->isActive) {
            return;
        }

        if (!$this->url) {
            $this->url = $this->getRealPath($this->generateLogFilename());
        }

        if (!is_dir(dirname($this->url))) {
            mkdir(dirname($this->url), 0755, true);
        }

        parent::write($record);
    }

    private function generateLogFilename(): string
    {
        return sprintf('batch_%s.log', sha1(uniqid(mt_rand(), true)));
    }

    /**
     * Note: code taken from ancestor's contructor.
     */
    private function setupStringChunkSize(): void
    {
        if (($phpMemoryLimit = Utils::expandIniShorthandBytes(ini_get('memory_limit'))) !== false) {
            if ($phpMemoryLimit > 0) {
                // use max 10% of allowed memory for the chunk size, and at least 100KB
                $this->streamChunkSize = min(static::MAX_CHUNK_SIZE, max((int) ($phpMemoryLimit / 10), 100 * 1024));
            } else {
                // memory is unlimited, set to the default 10MB
                $this->streamChunkSize = static::DEFAULT_CHUNK_SIZE;
            }
        } else {
            // no memory limit information, set to the default 10MB
            $this->streamChunkSize = static::DEFAULT_CHUNK_SIZE;
        }
    }
}
