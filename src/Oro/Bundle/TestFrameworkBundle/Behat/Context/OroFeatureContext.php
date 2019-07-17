<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;

/**
 * Basic feature context which may be used as parent class for other contexts.
 * Provides assert and spin functions.
 */
class OroFeatureContext extends RawMinkContext
{
    use AssertTrait, SpinTrait;

    public function waitForAjax()
    {
        $this->getDriver()->waitForAjax();
    }

    /**
     * {@inheritdoc}
     */
    public function getSession($name = null)
    {
        $session = parent::getSession($name);

        // start session if needed
        if (!$session->isStarted()) {
            $session->start();
        }

        return $session;
    }

    /**
     * @return OroSelenium2Driver
     */
    protected function getDriver()
    {
        return $this->getSession()->getDriver();
    }

    /**
     * Returns fixed step argument (with \\" replaced back to ")
     *
     * @param string $argument
     *
     * @return string
     */
    protected function fixStepArgument($argument)
    {
        return str_replace('\\"', '"', $argument);
    }

    /**
     * @param int|string $count
     * @return int
     */
    protected function getCount($count)
    {
        switch (trim($count)) {
            case '':
                return 1;
            case 'one':
                return 1;
            case 'two':
                return 2;
            default:
                return (int) $count;
        }
    }
}
