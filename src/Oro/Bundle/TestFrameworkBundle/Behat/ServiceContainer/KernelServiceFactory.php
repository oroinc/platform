<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer;

use Symfony\Component\HttpKernel\KernelInterface;

class KernelServiceFactory
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
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
