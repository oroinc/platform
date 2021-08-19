<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\AppKernelAwareInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Injects the kernel of the application being tested by behat.
 */
class AppKernelInitializer implements ContextInitializer
{
    private KernelInterface $appKernel;

    public function __construct(KernelInterface $appKernel)
    {
        $this->appKernel = $appKernel;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeContext(Context $context): void
    {
        if ($context instanceof AppKernelAwareInterface) {
            $context->setAppKernel($this->appKernel);
        }
    }
}
