<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Adds common methods for getting the kernel and the container of the application being tested by Behat.
 */
trait AppKernelAwareTrait
{
    protected ?KernelInterface $appKernel = null;

    protected ?ContainerInterface $appContainer = null;

    public function setAppKernel(KernelInterface $appKernel): void
    {
        $this->appKernel = $appKernel;
    }

    protected function getAppKernel(): KernelInterface
    {
        return $this->appKernel;
    }

    protected function getAppContainer(): ContainerInterface
    {
        return $this->appContainer ?: ($this->appContainer = $this->appKernel->getContainer());
    }
}
