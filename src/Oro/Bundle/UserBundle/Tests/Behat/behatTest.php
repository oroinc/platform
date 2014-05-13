<?php

namespace Oro\Bundle\UserBundle\Tests\Behat;

use Oro\Bundle\TestFrameworkBundle\Test\BehatTestCase;

/**
 * @dbIsolation
 */
class BehatTest extends BehatTestCase
{
    protected function setUp()
    {
        self::createClient();
    }

    public function testCreateUserAcceptanceCriteria()
    {
        $this->runFeature(__DIR__);
    }

    /**
     * @group selenium
     */
    public function testCreateUserAcceptanceCriteriaSelenium()
    {
        $this->runFeature(__DIR__, null, array(), 'behat.selenium.yml');
    }
}
