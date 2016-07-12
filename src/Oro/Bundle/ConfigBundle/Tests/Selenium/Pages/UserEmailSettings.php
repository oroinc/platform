<?php

namespace Oro\Bundle\ConfigBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

/**
 * Class Configuration
 *
 * @package Oro\Bundle\ConfigBundle\Tests\Selenium\Pages
 * @method UserEmailSettings openUserEmailSettings(string $bundlePath)
 * {@inheritdoc}
 */
class UserEmailSettings extends EmailSettings
{
    const URL = 'config/user/profile/platform/email_configuration';

    /**
     * @param array $imapSetting
     * @return $this
     */
    public function setImap($imapSetting)
    {
        $this->test->byXpath(
            "//div[@class='control-group imap-config check-connection control-group-checkbox']" .
            "//input[@data-ftid='email_configuration_oro_email___user_mailbox_value_imapConfiguration_useImap']"
        )->click();
        $this->waitForAjax();
        $this->test->byXPath(
            "//input[@data-ftid='email_configuration_oro_email___user_mailbox_value_imapConfiguration_imapHost']"
        )->value($imapSetting['host']);
        $this->test->byXPath(
            "//input[@data-ftid='email_configuration_oro_email___user_mailbox_value_imapConfiguration_imapPort']"
        )->value($imapSetting['port']);
        $this->test->byXPath(
            "//input[@data-ftid='email_configuration_oro_email___user_mailbox_value_imapConfiguration_user']"
        )->value($imapSetting['user']);
        $this->test->byXPath(
            "//input[@data-ftid='email_configuration_oro_email___user_mailbox_value_imapConfiguration_password']"
        )->value($imapSetting['password']);
        $encryption = $this->test
            ->select(
                $this->test
                    ->byXpath(
                        "//*[@data-ftid=" .
                        "'email_configuration_oro_email___user_mailbox_value_imapConfiguration_imapEncryption']"
                    )
            );
        $encryption->selectOptionByLabel($imapSetting['encryption']);
        $this->test->byXPath("//button[" .
            "@id='email_configuration_oro_email___user_mailbox_value_imapConfiguration_check_connection']")->click();
        $this->waitForAjax();
        $this->waitPageToLoad();
        $this->test->byXPath(
            "//div[@class='control-group folder-tree "
            . "control-group-oro_email_email_folder_tree']//input[@class='check-all']"
        )->click();

        $this->save();

        return $this;
    }
}
