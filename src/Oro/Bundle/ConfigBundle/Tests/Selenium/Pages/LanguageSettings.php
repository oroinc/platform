<?php

namespace Oro\Bundle\ConfigBundle\Tests\Selenium\Pages;

/**
 * Class Configuration
 *
 * @package Oro\Bundle\UserBundle\Tests\Selenium\Pages
 * @method LanguageSettings openLanguageSettings(string $bundlePath,  boolean $directLoad)
 * {@inheritdoc}
 */
class LanguageSettings extends Configuration
{
    const URL = '/config/system/platform/language_settings';

    /**
     * @param $language string
     * @return $this
     */
    public function download($language)
    {
        $element = $this->test
            ->byXPath(
                "//tr/td[normalize-space(.)='{$language}']" .
                "/following-sibling::td/button[normalize-space(.)='Download']"
            );
        $this->test->moveto($element);
        $element->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return $this;
    }

    /**
     * @param $language string
     * @return $this
     */
    public function enable($language)
    {
        $element = $this->test
            ->byXPath(
                "//tr/td[normalize-space(.)='{$language}']" .
                "/following-sibling::td/button[normalize-space(.)='Enable']"
            );
        $this->test->moveto($element);
        $element->click();
        $this->waitForAjax();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return $this;
    }

    /**
     * @param $language string
     * @return $this
     */
    public function disable($language)
    {
        $element = $this->test
            ->byXPath(
                "//tr/td[normalize-space(.)='{$language}']" .
                "/following-sibling::td/button[normalize-space(.)='Disable']"
            );
        $this->test->moveto($element);
        $element->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return $this;
    }

    /**
     * @param array $languages
     * @return $this
     */
    public function setSupported(array $languages)
    {
        $element = $this->test->byXPath(
            "//div[@class='control-group'][label[normalize-space(text())='Supported languages']]"
        );
        //check default checkbox and uncheck it
        $checkbox = $element
            ->using('xpath')
            ->value("div/div/div/input[contains(@id, 'use_parent_scope_value') and @type='checkbox']");

        return $this;
    }

    /**
     * @param string $language
     * @return $this
     */
    public function setDefault($language)
    {
        return $this;
    }
}
