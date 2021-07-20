<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\BrowserTabManager;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\BrowserTabManagerAwareInterface;

/**
 * Sets manager to the behat context.
 */
class BrowserTabManagerAwareInitializer implements ContextInitializer
{
    /**
     * @var BrowserTabManager
     */
    private $manager;

    public function __construct(BrowserTabManager $manager)
    {
        $this->manager = $manager;
    }

    public function initializeContext(Context $context)
    {
        if ($context instanceof BrowserTabManagerAwareInterface) {
            $context->setBrowserTabManager($this->manager);
        }
    }
}
