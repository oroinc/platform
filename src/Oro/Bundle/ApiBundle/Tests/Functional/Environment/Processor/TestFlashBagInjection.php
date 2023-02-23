<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\TestFlashBagDependedService;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * This processor is used to test that injection of the flash bag does not cause loading of the session.
 */
class TestFlashBagInjection implements ProcessorInterface
{
    private TestFlashBagDependedService $flashBagDependedService;

    public function __construct(TestFlashBagDependedService $flashBagDependedService)
    {
        $this->flashBagDependedService = $flashBagDependedService;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
    }
}
