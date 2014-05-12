<?php

namespace Oro\Bundle\TestFrameworkBundle\Test;

use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Login;

abstract class Selenium2TestCase extends \PHPUnit_Extensions_Selenium2TestCase
{
    protected $coverageScriptUrl = PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_TESTS_URL_COVERAGE;

    protected function setUp()
    {
        parent::setUp();

        $this->setHost(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_HOST);
        $this->setPort(intval(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_PORT));
        $this->setBrowser(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM2_BROWSER);
        $this->setBrowserUrl(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_TESTS_URL);
    }

    protected function tearDown()
    {
        $this->cookie()->clear();
        parent::tearDown();
    }

    public function prepareSession()
    {
        $session = parent::prepareSession();
        if (defined('PHPUNIT_SELENIUM_COVERAGE')) {
            $session->cookie()->remove('PHPUNIT_SELENIUM_TEST_ID');
            $this->url('/');
        }
        return $session;
    }

    /**
     * @param $userName
     * @param $password
     *
     * @return Login
     */
    public function login($userName = null, $password = null)
    {
        /** @var Login $login */
        $login = new Login($this);
        $login->setUsername(($userName) ? $userName : PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->setPassword(($password) ? $password : PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_PASS)
            ->submit();
        return $login;
    }
}
