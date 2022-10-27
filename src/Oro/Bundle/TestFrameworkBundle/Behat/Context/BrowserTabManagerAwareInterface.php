<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

/**
 * Interface for the context.
 */
interface BrowserTabManagerAwareInterface
{
    /**
     * @param BrowserTabManager $browserTabManager
     * @return void
     */
    public function setBrowserTabManager(BrowserTabManager $browserTabManager);
}
