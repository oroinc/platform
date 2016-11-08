<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class KernelIsolator implements IsolatorInterface
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

    /** {@inheritdoc} */
    public function start()
    {
        $this->kernel->boot();
    }

    /** {@inheritdoc} */
    public function beforeTest()
    {
        $this->kernel->boot();
    }

    /** {@inheritdoc} */
    public function afterTest()
    {
        $this->kernel->getContainer()->get('doctrine')->getManager()->clear();
        $this->kernel->shutdown();
    }

    /** {@inheritdoc} */
    public function terminate()
    {}

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return true;
    }
}