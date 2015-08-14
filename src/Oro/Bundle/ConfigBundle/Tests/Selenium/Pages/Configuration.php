<?php

namespace Oro\Bundle\ConfigBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

/**
 * Class Configuration
 *
 * @package Oro\Bundle\ConfigBundle\Tests\Selenium\Pages
 * @method Configuration openConfiguration(string $bundlePath)
 * {@inheritdoc}
 */
class Configuration extends AbstractPage
{
    const URL = 'config/system';

    /**
     * @param bool $directLoad
     * @return LanguageSettings
     */
    public function openLanguageSettings($directLoad = false)
    {
        if (!$directLoad) {
            //expand tree
            $treeElements = $this->test->elements(
                $this->test
                    ->using('xpath')
                    ->value(
                        "//a[normalize-space(.)='Language settings']" .
                        "/ancestor::div//a[contains(@class,'accordion-toggle collapsed')]"
                    )
            );
            foreach ($treeElements as $treeElement) {
                /** @var  $treeElement \PHPUnit_Extensions_Selenium2TestCase_Element */
                $treeElement->click();
                $this->waitPageToLoad();
                $this->waitForAjax();
            }

            $this->test->byXPath("//a[normalize-space(.)='Language settings']")->click();
            $this->waitPageToLoad();
            $this->waitForAjax();

        }

        return new LanguageSettings($this->test, $directLoad);
    }

    public function save()
    {
        $this->test->byXPath("//button[normalize-space(text())='Save settings']")->click();
        return $this;
    }
}
