<?php

namespace Oro\Bundle\AttachmentBundle;

use Symfony\Component\Process\ExecutableFinder;

/**
 * Responsible for searching pngquant, jpegoptim libraries.
 */
class ProcessorExecutableFinder
{
    const EXTRA_DIRS = ['/usr/bin', '/usr/local/bin', '/opt/bin', '/bin'];

    /**
     * @var ExecutableFinder
     */
    private $executableFinder;

    public function __construct()
    {
        $this->executableFinder = new ExecutableFinder();
    }

    public function find(string $library): ?string
    {
        $binary = $this->executableFinder->find($library, null, self::EXTRA_DIRS);
        if (!$binary) {
            $command = '\\' === \DIRECTORY_SEPARATOR ? 'where' : 'command -v';
            $binary = strtok(exec($command . ' ' . escapeshellarg($library)), PHP_EOL);
        }

        return $binary ?? null;
    }
}
