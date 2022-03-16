<?php

namespace Oro\Component\MessageQueue\Consumption\Dbal;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Process;

/**
 * Gets a list of processes PIDs.
 */
class DbalCliProcessManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function getListOfProcessesPids(string $searchTerm): array
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $cmd = 'WMIC path win32_process get Processid,Commandline | findstr %s | findstr /V findstr';
            $searchRegExp = '/\s+(\d+)\s*$/Usm';
        } else {
            $cmd = "pgrep -f '%s'";
            $searchRegExp = '/^(\d+)$/Usm';
        }
        $cmd = sprintf($cmd, escapeshellarg($searchTerm));

        $process = Process::fromShellCommandline($cmd);
        try {
            // It is possible that checking for running processes may result in a runtime exception. It is not
            // a reason to interrupt consumer.
            $process->run();
            $output = $process->getOutput();
        } catch (\RuntimeException $exception) {
            $output = '';

            $this->logger->error(
                sprintf('Failed to get a list of running processes PIDs: %s', $exception->getMessage()),
                ['exception' => $exception, 'command' => $cmd]
            );
        }

        $pids = [];
        $lines = preg_split('/$\R?^/m', $output);
        foreach ($lines as $line) {
            preg_match($searchRegExp, $line, $matches);
            if (count($matches) > 1 && !empty($matches[1])) {
                $pids[] = (int)$matches[1];
            }
        }

        return $pids;
    }
}
