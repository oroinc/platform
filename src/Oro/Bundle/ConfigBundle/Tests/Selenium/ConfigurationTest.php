<?php

namespace Oro\Bundle\ConfigBundle\Tests\Selenium;

use Oro\Bundle\ConfigBundle\Tests\Selenium\Pages\Configuration;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

class ConfigurationTest extends Selenium2TestCase
{
     /**
     * @return string
     */
    public function testOpenConfiguration()
    {
        $login = $this->login();
        /** @var Configuration $login */
        $login->openConfiguration('Oro\Bundle\ConfigBundle')
            ->assertTitle('Configuration - System')
            ->openLanguageSettings()
            ->download('Persian (Iran)')
            ->enable('Persian (Iran)')
            ->disable('Persian (Iran)');
    }
}
