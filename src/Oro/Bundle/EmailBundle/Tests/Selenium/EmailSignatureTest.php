<?php

namespace Oro\Bundle\EmailBundle\Tests\Selenium;

use Oro\Bundle\ConfigBundle\Tests\Selenium\Pages\EmailSettings;
use Oro\Bundle\EmailBundle\Tests\Selenium\Pages\Email;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users;

/**
 * Class SendEmailTest
 *
 * @package Oro\Bundle\EmailBundle\Tests\Selenium
 */
class EmailSignatureTest extends Selenium2TestCase
{
    /**
     * Test to check add system email signature functionality
     * test sets empty user signature
     */
    public function testAddSystemEmailSignature()
    {
        $signature = 'System signature_'.mt_rand(10, 99);
        $login = $this->login();
        /** @var EmailSettings $login */
        $login->openEmailSettings('Oro\Bundle\ConfigBundle')
            ->setSignature($signature)
            ->save()
            ->assertMessage('Configuration saved');
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->open([PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN])
            ->runActionInGroup('Send email');
        /** @var Email $login */
        $login->openEmail('Oro\Bundle\EmailBundle')
            ->assertBodyContains($signature)
            ->closeWidgetWindow();

        return $signature;
    }

    /**
     * Test to check add functionality for user email signature
     */
    public function testAddUserEmailSignature()
    {
        $this->markTestSkipped('Signature configuration was moved to user configuration page');
        $signature = "Test signature for user_".mt_rand(10, 99);

        $login = $this->login();
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->open([PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN])
            ->edit()
            ->setSignature($signature)
            ->save()
            ->assertMessage('User saved');
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->open([PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN])
            ->runActionInGroup('Send email');
        /** @var Email $login */
        $login->openEmail('Oro\Bundle\EmailBundle')
            ->assertBodyContains($signature)
            ->closeWidgetWindow();
    }

    /**
     * Test to check that user signature is prior to system signature
     * also checks that if user signature deleted system appears
     * @depends testAddSystemEmailSignature
     * @depends testAddUserEmailSignature
     * @param $signature
     */
    public function testPriorityOfSystemSignatureOverUser($signature)
    {
        $this->markTestSkipped('Signature configuration was moved to user configuration page');
        $login = $this->login();
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->open([PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN])
            ->edit()
            ->setSignature('')
            ->save()
            ->assertMessage('User saved');
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->open([PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN])
            ->runActionInGroup('Send email');
        /** @var Email $login */
        $login->openEmail('Oro\Bundle\EmailBundle')
            ->assertBodyContains($signature)
            ->closeWidgetWindow();
    }
}
