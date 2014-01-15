<?php

namespace Oro\Bundle\SecurityBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

/**
 * Class Test404
 *
 * @package Oro\Bundle\SecurityBundle\Tests\Selenium
 */
class Test404 extends Selenium2TestCase
{
    public function test404()
    {
        $login = $this->login();
        $login->openAclCheck('Oro\Bundle\SecurityBundle')
            ->assertAcl('404', '404 - Not Found')
            ->assertElementPresent(
                "//div[@class='pagination-centered popup-box-errors'][contains(., '404 Not Found')]"
            );

    }
}
