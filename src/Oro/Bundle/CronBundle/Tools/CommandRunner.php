<?php

namespace Oro\Bundle\CronBundle\Tools;

use Symfony\Component\Process\PhpExecutableFinder;

/**
 * This runner runs a console command with parameters in the background without locking the main thread.
 * Please note, that this class should be used only from the console commands as it uses $_SERVER['argv'][0]
 * to get the path to symfony "console" file.
 */
class CommandRunner
{
    /**
     * Runs the command in background process without the lock of main stream.
     *
     * @param string $command
     * @param array  $params
     * @param string $outputFile
     */
    public static function runCommand($command, $params, $outputFile = '/dev/null')
    {
        $phpFinder = new PhpExecutableFinder();
        $phpPath   = $phpFinder->find();

        // convert command arguments to the string
        $parametersString = '';
        foreach ($params as $name => $value) {
            if ($name && '-' === $name[0]) {
                if ($value === true) {
                    $parametersString .= ' ' . $name;
                } elseif ($value !== false) {
                    $parametersString .= ' ' . sprintf('%s=%s', $name, $value);
                }
            } else {
                $parametersString .= ' ' . $value;
            }
        }

        // create command string
        $runCommand = sprintf(
            '%s %s %s%s',
            $phpPath,
            $_SERVER['argv'][0],
            $command,
            $parametersString
        );

        // workaround for Windows
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $wsh = new \COM('WScript.shell');
            $wsh->Run($runCommand, 0, false);
            return;
        }

        // run command
        shell_exec(sprintf(
            '%s > %s 2>&1 & echo $!',
            $runCommand,
            $outputFile
        ));
    }
}
