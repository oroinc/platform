<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Factory for accessing services from the Symfony kernel's dependency injection container.
 *
 * This factory wraps a Symfony kernel instance and provides convenient access to services
 * from the DI container, with methods to boot and shutdown the kernel as needed.
 */
class KernelServiceFactory
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->kernel->boot();
    }

    /**
     * @param $id
     * @return object
     */
    public function get($id)
    {
        return $this->kernel->getContainer()->get($id);
    }

    public function boot()
    {
        $this->kernel->boot();
    }

    public function shutdown()
    {
        $this->kernel->shutdown();
    }
}
