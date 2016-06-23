<?php

namespace Oro\Bundle\ImapBundle\Bundle\Tests\Selenium;

use Oro\Bundle\EmailBundle\Tests\Selenium\Pages\Emails;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users;

/**
 * Class ImapTest
 *
 * @package Oro\Bundle\ImapBundle\Tests\Selenium
 */
class ImapTest extends Selenium2TestCase
{
    /**
     * Test to check that user IMAP sync works
     * @return string
     */
    public function testUserImapSync()
    {
        $this->markTestSkipped('Imap configuration was moved to user configuration page');
        $imapSetting = array(
            'host' => PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_HOST,
            'port' => '143',
            'encryption' => '',
            'user' => 'mailbox1',
            'password' => 'eF3ar4ic'
        );

        $login = $this->login();
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->open([PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN])
            ->edit()
            ->setImap($imapSetting)
            ->save();
        exec("app/console oro:cron:imap-sync --env prod");
        /** @var Emails $login */
        $login->openEmails('Oro\Bundle\EmailBundle')
            ->assertElementNotPresent(
                "//div[@class='no-data-visible floatThead-fixed floatThead']//span[contains(., 'No records found')]",
                'No emails were synced'
            );
    }
}
