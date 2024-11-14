<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Driver;

use Behat\MinkExtension\ServiceContainer\Driver\Selenium2Factory;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Factory to build selenium2 driver.
 */
class OroSelenium2Factory extends Selenium2Factory
{
    #[\Override]
    public function buildDriver(array $config)
    {
        $definition = parent::buildDriver($config);
        $definition->setClass('Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver');
        $definition->addMethodCall(
            'setSessionHolder',
            [new Reference('oro_test.behat.watch_mode.session_holder')]
        );

        return $definition;
    }

    #[\Override]
    public function getDriverName()
    {
        return 'oroSelenium2';
    }
}
