<?php

namespace Oro\Bundle\ConfigBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

/**
 * Class Configuration
 *
 * @package Oro\Bundle\ConfigBundle\Tests\Selenium\Pages
 * @method UserImapSettings openUserImapSettings(string $bundlePath)
 * {@inheritdoc}
 */
class UserImapSettings extends AbstractPage
{
    const URL = 'config/user/profile/emailsettings/platform/email_configuration';

    public function setImap($imapSetting)
    {
        $this->test->byXpath(
            "//div[@class='control-group imap-config check-connection control-group-checkbox']" .
            "//input[@data-ftid='oro_user_emailsettings_form_imapConfiguration_useImap']"
        )->click();
        $this->waitForAjax();
        $this->test->byXPath(
            "//input[@data-ftid='oro_user_emailsettings_form_imapConfiguration_imapHost']"
        )->value($imapSetting['host']);
        $this->test->byXPath(
            "//input[@data-ftid='oro_user_emailsettings_form_imapConfiguration_imapPort']"
        )->value($imapSetting['port']);
        $this->test->byXPath(
            "//input[@data-ftid='oro_user_emailsettings_form_imapConfiguration_user']"
        )->value($imapSetting['user']);
        $this->test->byXPath(
            "//input[@data-ftid='oro_user_emailsettings_form_imapConfiguration_password']"
        )->value($imapSetting['password']);
        $this->encryption = $this->test
            ->select(
                $this->test->byXpath("//*[@data-ftid='oro_user_emailsettings_form_imapConfiguration_imapEncryption']")
            );
        $this->encryption->selectOptionByLabel($imapSetting['encryption']);
        $this->test->byXPath("//button[@id='oro_user_emailsettings_form_imapConfiguration_check_connection']")->click();
        $this->waitForAjax();
        $this->waitPageToLoad();
        $this->test->byXPath(
            "//div[@class='control-group folder-tree "
            . "control-group-oro_email_email_folder_tree']//input[@class='check-all']"
        )->click();
        $this->test->byXPath("//button[normalize-space(.) = 'Save and Close']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return $this;
    }
}
