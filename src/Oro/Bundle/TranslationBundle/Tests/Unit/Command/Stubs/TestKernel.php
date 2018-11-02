<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Command\Stubs;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Kernel stub class
 */
class TestKernel extends Kernel
{
    /**
     * @param string $rootDir
     */
    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
        parent::__construct('test_stub_env', true);
    }

    public function init()
    {
    }

    public function registerBundles()
    {
        return [];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }
}
