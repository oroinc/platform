<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Driver;

use Behat\MinkExtension\ServiceContainer\Driver\Selenium2Factory;

class OroSelenium2Factory extends Selenium2Factory
{
    /**
     * {@inheritdoc}
     */
    public function buildDriver(array $config)
    {
        $definition = parent::buildDriver($config);
        $definition->setClass('Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver');

        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverName()
    {
        return 'oroSelenium2';
    }
}
