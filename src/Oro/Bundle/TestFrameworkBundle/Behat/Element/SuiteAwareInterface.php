<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Testwork\Suite\Suite;

interface SuiteAwareInterface
{
    public function setSuite(Suite $suite);
}
