<?php

namespace Oro\Bundle\AssetBundle;

use Composer\Semver\Semver;
use Symfony\Component\Process\Process;

/**
 * Check version of installed NodeJs against constraint
 */
class NodeJsVersionChecker
{
    /**
     * @param string $nodeJsExecutable
     * @param string $constraints
     * @return bool
     */
    public static function satisfies(string $nodeJsExecutable, string $constraints): bool
    {
        $process = new Process($nodeJsExecutable.' -v');
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(
                sprintf('Failed to check "%s" version. %s', $nodeJsExecutable, $process->getErrorOutput())
            );
        }
        $version = $process->getOutput();

        return Semver::satisfies($version, $constraints);
    }
}
