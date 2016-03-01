<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Integrations
 * @package Oro\Bundle\IntegrationBundle\Tests\Selenium\Pages
 * @method Integrations openIntegrations(string $bundlePath)
 * @method Integration add()
 * @method Integration open(array $filter)
 * {@inheritdoc}
 */
class Integrations extends AbstractPageFilteredGrid
{
    const NEW_ENTITY_BUTTON = "//a[@title='Create Integration']";
    const URL = 'integration';

    public function entityNew()
    {
        return new Integration($this->test);
    }

    public function entityView()
    {
        new Integration($this->test);
    }

    /**
     * @param $integration
     * @return Integration
     */
    public function edit($integration)
    {
        $this->open($integration);

        return new Integration($this->test);
    }
}
