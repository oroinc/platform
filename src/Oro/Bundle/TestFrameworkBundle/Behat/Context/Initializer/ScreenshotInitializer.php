<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Oro\Bundle\TestFrameworkBundle\Behat\Artifacts\ScreenshotGenerator;

/**
 * Initializes screenshot generator in Behat contexts if setScreenshotGenerator method exists
 */
class ScreenshotInitializer implements ContextInitializer
{
    public function __construct(private ScreenshotGenerator $screenshotGenerator)
    {
    }

    #[\Override]
    public function initializeContext(Context $context)
    {
        if (\method_exists($context, 'setScreenshotGenerator')) {
            $context->setScreenshotGenerator($this->screenshotGenerator);
        }
    }
}
