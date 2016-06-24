<?php

namespace Oro\Bundle\ImapBundle\Bundle\Tests\Selenium;

use Oro\Bundle\ConfigBundle\Tests\Selenium\Pages\UserImapSettings;
use Oro\Bundle\EmailBundle\Tests\Selenium\Pages\Emails;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

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
        $imapSetting = array(
            'host' => PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_HOST,
            'port' => '143',
            'encryption' => '',
            'user' => 'mailbox1',
            'password' => 'eF3ar4ic'
        );

        $login = $this->login();
        /** @var UserImapSettings $login */
        $login->openUserImapSettings('Oro\Bundle\ConfigBundle')->setImap($imapSetting);
        exec("app/console oro:cron:imap-sync --env prod");
        /** @var Emails $login */
        $login->openEmails('Oro\Bundle\EmailBundle')
            ->assertElementNotPresent(
                "//div[@class='no-data-visible floatThead-fixed floatThead']//span[contains(., 'No records found')]",
                'No emails were synced'
            );
    }
}
