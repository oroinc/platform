<?php
namespace Oro\Component\MessageQueue\Consumption\Dbal;

use Symfony\Component\Process\Process;

class DbalCliProcessManager
{
    /**
     * @param string $searchTerm
     *
     * @return int[]
     */
    public function getListOfProcessesPids($searchTerm)
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $cmd = 'WMIC path win32_process get Processid,Commandline | findstr %s | findstr /V findstr';
            $searchRegExp = '/\s+(\d+)\s*$/Usm';
        } else {
            $cmd = 'ps ax | grep %s | grep -v grep';
            $searchRegExp = '/^\s*(\d+)\s+/Usm';
        }
        $cmd = sprintf($cmd, escapeshellarg($searchTerm));

        $process = new Process($cmd);
        $process->run();
        $output = $process->getOutput();

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
