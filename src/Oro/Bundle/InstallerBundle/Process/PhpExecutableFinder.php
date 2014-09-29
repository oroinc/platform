<?php

namespace Oro\Bundle\InstallerBundle\Process;

use Symfony\Component\Process\PhpExecutableFinder as BasePhpExecutableFinder;

class PhpExecutableFinder
{
    /**
     * @var BasePhpExecutableFinder
     */
    protected $finder;

    public function __construct()
    {
        $this->finder = new BasePhpExecutableFinder();
    }

    /**
     * Finds The PHP executable.
     *
     * @param bool $includeArgs Whether or not include command arguments
     *
     * @return string|false The PHP executable path or false if it cannot be found
     */
    public function find($includeArgs = true)
    {
        if ($php = getenv('ORO_PHP_PATH')) {
            if (is_executable($php)) {
                return $php;
            }
        }

        return $this->finder->find($includeArgs);
    }
}
