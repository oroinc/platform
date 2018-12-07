<?php

namespace Oro\Bundle\AssetBundle;

use Symfony\Component\Process\ExecutableFinder;

/**
 * Find executable for NodeJs and NPM.
 */
class NodeJsExecutableFinder
{
    protected const NODEJS_EXECUTABLE_NAMES = ['node', 'nodejs'];

    /**
     * @var ExecutableFinder
     */
    private $executableFinder;

    public function __construct()
    {
        $this->executableFinder = new ExecutableFinder();
    }

    /**
     * @return null|string
     */
    public function findNodeJs(): ?string
    {
        foreach (self::NODEJS_EXECUTABLE_NAMES as $engine) {
            $executable = $this->executableFinder->find($engine);
            if (null !== $executable) {
                return $executable;
            }
        }

        return null;
    }

    /**
     * @return null|string
     */
    public function findNpm(): ?string
    {
        return $this->executableFinder->find('npm');
    }
}
