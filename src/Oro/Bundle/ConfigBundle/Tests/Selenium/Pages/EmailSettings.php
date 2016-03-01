<?php

namespace Oro\Bundle\ConfigBundle\Tests\Selenium\Pages;

/**
 * Class Configuration
 *
 * @package Oro\Bundle\UserBundle\Tests\Selenium\Pages
 * @method EmailSettings openEmailSettings(string $bundlePath)
 * {@inheritdoc}
 */
class EmailSettings extends Configuration
{
    const URL = '/config/system/platform/email_configuration';

    /**
     * @param $value string
     * @return $this
     */
    public function setSignature($value)
    {
        $checkbox = "//input[@data-ftid='email_configuration_oro_email___signature_use_parent_scope_value']".
            "[@checked='checked']";
        if ($this->isElementPresent($checkbox)) {
            $this->test->byXPath($checkbox)->click();
            $this->waitForAjax();
        }

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
                'script' => sprintf('tinyMCE.activeEditor.setContent(\'%s\')', $value),
                'args' => [],
            ]
        );

        return $this;
    }

    public function setSignatureOff()
    {
        $checkbox = "//input[@data-ftid='email_configuration_oro_email___signature_use_parent_scope_value']";
        if (!$this->isElementPresent($checkbox."[@checked='checked']")) {
            $this->test->byXPath($checkbox)->click();
            $this->waitForAjax();
        }

        return $this;
    }
}
