<?php

namespace Oro\Bundle\EmailBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class Email
 *
 * @package Oro\Bundle\EmailBundle\Bundle\Pages
 * @method Emails openEmails(string $bundlePath)
 * @method Email openEmail(string $bundlePath)
 */
class Email extends AbstractPageEntity
{
    /**
     * @param $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->test->byXPath("//input[@data-ftid='oro_email_email_subject']")->value($subject);
        return $this;
    }

    /**
     * @param $sendTo
     * @return $this
     */
    public function setTo($sendTo)
    {
        $this->test->byXPath("//div[starts-with(@id,'s2id_oro_email_email_to')]")->click();
        $this->waitForAjax();
        $this->test->byXPath("//div[starts-with(@id,'s2id_oro_email_email_to')]//input")->value($sendTo);
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[@id='select2-drop']//div[contains(., '{$sendTo}')]",
            "Entity autocomplete doesn't return search value"
        );
        $this->test->byXPath("//div[@id='select2-drop']//div[contains(., '{$sendTo}')]")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    /**
     * @param $content string
     * @return $this
     */
    public function setBody($content)
    {
        $this->test->waitUntil(
            function (\PHPUnit_Extensions_Selenium2TestCase $testCase) {
                return $testCase->execute(
                    [
                        'script' => 'return tinyMCE.activeEditor.initialized',
                        'args' => [],
                    ]
                );
            },
            intval(MAX_EXECUTION_TIME)
        );

        $this->test->execute(
            [
                'script' => sprintf('tinyMCE.activeEditor.setContent(\'%s\')', $content),
                'args' => [],
            ]
        );

        return $this;
    }

    public function send()
    {
        $this->test->byXPath("//div[@class='widget-actions-section']//button[contains(., 'Send')]")->click();
        $this->waitForAjax();
        $this->waitPageToLoad();
        return $this;
    }
}
