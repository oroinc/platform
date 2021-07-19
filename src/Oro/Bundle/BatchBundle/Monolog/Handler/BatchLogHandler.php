<?php

namespace Oro\Bundle\BatchBundle\Monolog\Handler;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

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
}
