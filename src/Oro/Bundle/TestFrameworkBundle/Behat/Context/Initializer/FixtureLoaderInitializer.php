<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoader;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderAwareInterface;

class FixtureLoaderInitializer implements ContextInitializer
{
    /**
     * @var FixtureLoader
     */
    protected $fixtureLoader;

    public function __construct(FixtureLoader $fixtureLoader)
    {
        $this->fixtureLoader = $fixtureLoader;
    }

    #[\Override]
    public function initializeContext(Context $context)
    {
        if ($context instanceof FixtureLoaderAwareInterface) {
            $context->setFixtureLoader($this->fixtureLoader);
        }
    }
}
