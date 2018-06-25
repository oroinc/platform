<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Command\Stubs;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Kernel stub class
 */
class TestKernel extends Kernel
{
    public function __construct()
    {
        parent::__construct('test_stub_env', true);
    }

    public function init()
    {
    }

    public function getRootDir()
    {
        return sys_get_temp_dir() . '/translation-test-stub-cache';
    }

    public function registerBundles()
    {
        return [];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }
}
