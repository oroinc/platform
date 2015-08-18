<?php

namespace Oro\Bundle\ReportBundle\Tests\Selenium;

use Oro\Bundle\ReportBundle\Tests\Selenium\Pages\Reports;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

class ReportsTest extends Selenium2TestCase
{
     /**
     * @return string
     */
    public function testCreateReport()
    {
        $reportName = 'Report_' . mt_rand();
        $login = $this->login();
        /** @var Reports $login */
        $login->openReports('Oro\Bundle\ReportBundle')
            ->assertTitle('All - Manage Custom Reports - Reports & Segments')
            ->add()
            ->setName($reportName)
            ->setEntity('User')
            ->setType('Table')
            ->setOrganization('Main')
            ->addColumn(['First name', 'Last name', 'Username'])
            ->addFieldCondition('Username', 'Admin')
            ->save();
    }
}
