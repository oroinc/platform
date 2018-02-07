<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Command\Stubs;

use Symfony\Component\ClassLoader\UniversalClassLoader;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

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
        $loader = new UniversalClassLoader();

        $loader->registerNamespace('SomeProject\\', array(__DIR__));
        $loader->registerNamespace('SomeAnotherProject\\', array(__DIR__));
        $loader->register();

        return array(
            new \SomeProject\Bundle\SomeBundle\SomeBundle(),
            new \SomeAnotherProject\Bundle\SomeAnotherBundle\SomeAnotherBundle()
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }
}
