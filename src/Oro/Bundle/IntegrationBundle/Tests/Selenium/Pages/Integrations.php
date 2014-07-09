<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Integrations
 * @package Oro\Bundle\IntegrationBundle\Tests\Selenium\Pages
 * @method Integrations openIntegrations openIntegrations(string)
 * {@inheritdoc}
 */
class Integrations extends AbstractPageFilteredGrid
{
    const URL = 'integration';

    public function __construct($testCase, $redirect = true)
    {
        $this->redirectUrl = self::URL;
        parent::__construct($testCase, $redirect);
    }

    /**
     * @return Integration
     */
    public function add()
    {
        $this->test->byXPath("//a[@title='Create Integration']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new Integration($this->test);
    }

    /**
     * @param array $entityData
     * @return Integration
     */
    public function open($entityData = array())
    {
        $form = $this->getEntity($entityData, 1);
        $form->click();
        sleep(1);
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new Integration($this->test);
    }
}
