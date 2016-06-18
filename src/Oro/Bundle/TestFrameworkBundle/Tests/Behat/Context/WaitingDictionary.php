<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

trait WaitingDictionary
{
    /**
     * Wait PAGE load
     * @param int $time Time should be in milliseconds
     */
    protected function waitPageToLoad($time = 15000)
    {
        $this->getSession()->wait(
            $time,
            '"complete" == document["readyState"] '.
            '&& (typeof($) != "undefined" '.
            '&& document.title !=="Loading..." '.
            '&& $ !== null '.
            '&& false === $( "div.loader-mask" ).hasClass("shown"))'
        );
    }

    /**
     * Wait AJAX request
     * @param int $time Time should be in milliseconds
     */
    protected function waitForAjax($time = 15000)
    {
        $this->waitPageToLoad($time);

        $jsAppActiveCheck = <<<JS
        (function () {
            var isAppActive = false;
            try {
                if (!window.mediatorCachedForSelenium) {
                    window.mediatorCachedForSelenium = require('oroui/js/mediator');
                }
                isAppActive = window.mediatorCachedForSelenium.execute('isInAction');
            } catch (e) {
                return false;
            }

            return !(jQuery && (jQuery.active || jQuery(document.body).hasClass('loading'))) && !isAppActive;
        })();
JS;
        $this->getSession()->wait($time, $jsAppActiveCheck);
    }
}
