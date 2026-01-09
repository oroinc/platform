<?php

namespace Oro\Bundle\AssetBundle;

use Symfony\Component\Process\Process;

/**
 * Creates a Process instance for running asset build commands with the configured JS engine.
 */
class AssetCommandProcessFactory
{
    public function __construct(private string $jsEnginePath)
    {
    }

    /**
     * @param array $command The command line to run
     * @param string $cwd The working directory or null to use the working dir of the current PHP process
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @return Process
     */
    public function create(array $command, string $cwd, $timeout = null): Process
    {
        if (!$this->jsEnginePath) {
            throw new \RuntimeException('Js engine path is not found');
        }

        $process = new Process(array_merge([$this->jsEnginePath], $command), $cwd);
        $process->setTimeout($timeout);

        return $process;
    }
}
