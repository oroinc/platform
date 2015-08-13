<?php

namespace Oro\Bundle\ReportBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Reports
 *
 * @package Oro\Bundle\ReportBundle\Tests\Selenium\Pages
 * @method Reports openReports($bundlePath)
 * @method Report add()
 * {@inheritdoc}
 */
class Reports extends AbstractPageFilteredGrid
{
    const URL = 'report';
    const NEW_ENTITY_BUTTON = "//a[@title='Create Report']";

    public function entityView()
    {
        return new ReportData($this->test);
    }

    public function entityNew()
    {
        return new Report($this->test);
    }
}
