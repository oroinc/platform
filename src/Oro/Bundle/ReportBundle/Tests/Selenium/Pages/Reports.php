<?php

namespace Oro\Bundle\ReportBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Reports
 *
 * @package Oro\Bundle\ReportBundle\Tests\Selenium\Pages
 * @method Reports openReports openReports(string)
 * {@inheritdoc}
 */
class Reports extends AbstractPageFilteredGrid
{
    const URL = 'report';

    public function __construct($testCase, $redirect = true)
    {
        $this->redirectUrl = self::URL;
        parent::__construct($testCase, $redirect);
    }

    public function open($entityData = array())
    {
        $contact = $this->getEntity($entityData);
        $contact->click();
        sleep(1);
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new ReportData($this->test);
    }
}
