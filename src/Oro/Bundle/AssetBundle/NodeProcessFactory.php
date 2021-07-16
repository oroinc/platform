<?php

namespace Oro\Bundle\AssetBundle;

use Symfony\Component\Process\Process;

/**
 * Run command using NodeJs engine
 */
class NodeProcessFactory
{
    /**
     * @var string
     */
    private $jsEngine;

    public function __construct(string $jsEnginePath)
    {
        $this->jsEngine = $jsEnginePath;
    }

    /**
     * @param array         $command The command line to run
     * @param string         $cwd The working directory or null to use the working dir of the current PHP process
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @return Process
     */
    public function create(array $command, string $cwd, $timeout = null): Process
    {
        if (!$this->jsEngine) {
            throw new \RuntimeException('JS engine not found');
        }
        $process = new Process(array_merge([$this->jsEngine], $command), $cwd);
        $process->setTimeout($timeout);

        // some workaround when this command is launched from web
        if (isset($_SERVER['PATH'])) {
            $env = $_SERVER;
            if (isset($env['Path'])) {
                unset($env['Path']);
            }
            $process->setEnv($env);
        }

        return $process;
    }
}
