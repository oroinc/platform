<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Testwork\Suite\Suite;

/**
 * Defines the contract for classes that need to be aware of the current test suite.
 *
 * Classes implementing this interface can be injected with the current Behat test suite,
 * allowing them to access suite-specific configuration and metadata during test execution.
 */
interface SuiteAwareInterface
{
    public function setSuite(Suite $suite);
}
