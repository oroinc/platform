<?php

namespace Oro\Bundle\InstallerBundle\Process;

use Symfony\Component\Process\PhpExecutableFinder as BasePhpExecutableFinder;

/**
 * @deprecated since 1.9 use PHP_PATH instead
 *
 * @see \Symfony\Component\Process\PhpExecutableFinder
 */
class PhpExecutableFinder
{
    /** @var BasePhpExecutableFinder */
    protected $finder;

    public function __construct()
    {
        $this->finder = new BasePhpExecutableFinder();
    }

    /**
     * @see \Symfony\Component\Process\PhpExecutableFinder::find
     */
    public function find($includeArgs = true)
    {
        $php = getenv('ORO_PHP_PATH');
        if ($php && is_executable($php)) {
            return $php;
        }

        return $this->finder->find($includeArgs);
    }
}
