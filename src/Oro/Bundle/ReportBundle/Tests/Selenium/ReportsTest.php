<?php

namespace Oro\Bundle\ReportBundle\Tests\Selenium;

use Oro\Bundle\EntityConfigBundle\Tests\Selenium\Pages\ConfigEntities;
use Oro\Bundle\NoteBundle\Tests\Selenium\Pages\Notes;
use Oro\Bundle\ReportBundle\Tests\Selenium\Pages\Reports;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users;

class ReportsTest extends Selenium2TestCase
{
    /**
     * Test to check that report can be created
     * @return string
     */
    public function testCreateReport()
    {
        $reportName = 'Report_' . mt_rand();
        $userName = 'admin';
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
            ->addFilterCondition('Field condition', 'Username', $userName)
            ->save();
        /** @var Reports $login */
        $login->openReports('Oro\Bundle\ReportBundle')
            ->filterBy('Name', $reportName)
            ->open(array($reportName))
            ->filterBy('Username', $userName)
            ->entityExists(array($userName));

        return $reportName;
    }

    /**
     * Test to check deletion of existing report
     * @depends testCreateReport
     * @param $reportName
     */
    public function testDeleteReport($reportName)
    {
        $login = $this->login();
        /** @var Reports $login */
        $login->openReports('Oro\Bundle\ReportBundle')
            ->filterBy('Name', $reportName)
            ->delete($reportName)
            ->filterBy('Name', $reportName)
            ->assertNoDataMessage('No report was found to match your search');
    }

    /**
     * Test to check that report can be created by data audit filter
     */
    public function testReportByDataAudit()
    {
        $date = new \DateTime();
        $dataFilter['Start'] = $date->sub(new \DateInterval('P1D'))->format('M j, Y');
        $dataFilter['End'] = $date->add(new \DateInterval('P2D'))->format('M j, Y');
        $userName = 'admin';

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
            ->addFilterCondition('Data audit', 'Middle name', $dataFilter)
            ->save();
        /** @var Reports $login */
        $login->openReports('Oro\Bundle\ReportBundle')
            ->filterBy('Name', $reportName)
            ->open(array($reportName))
            ->assertNoDataMessage('No records found');
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', $userName)
            ->open(array($userName))
            ->edit()
            ->setMiddleName('Junior')
            ->save()
            ->assertMessage('User saved');
        /** @var Reports $login */
        $login->openReports('Oro\Bundle\ReportBundle')
            ->filterBy('Name', $reportName)
            ->open(array($reportName))
            ->filterBy('Username', $userName)
            ->entityExists(array($userName));
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', $userName)
            ->open(array($userName))
            ->edit()
            ->setMiddleName('')
            ->save()
            ->assertMessage('User saved');
    }

    /**
     * Test to check that report can be created by activity filter
     * @return string
     */
    public function testReportByActivity()
    {
        $date = new \DateTime();
        $dataFilter['Start'] = $date->sub(new \DateInterval('P1D'))->format('M j, Y');
        $dataFilter['End'] = $date->add(new \DateInterval('P2D'))->format('M j, Y');
        $userName = 'admin';

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
            ->addFilterCondition('Activity', 'Created at', $dataFilter)
            ->save();
        /** @var Reports $login */
        $login->openReports('Oro\Bundle\ReportBundle')
            ->filterBy('Name', $reportName)
            ->open(array($reportName))
            ->assertNoDataMessage('No records found');
        /** @var ConfigEntities $login */
        $login->openConfigEntities('Oro\Bundle\EntityConfigBundle')
            ->filterBy('Name', 'User', 'is equal to')
            ->open(['User'])
            ->edit()
            ->enableNotes()
            ->save()
            ->updateSchema();
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', $userName)
            ->open(array($userName));
        /** @var Notes $login */
        $login->openNotes('Oro\Bundle\NoteBundle')
            ->addNote()
            ->setNoteMessage('Test note')
            ->saveNote()
            ->assertMessage('Note saved');
        /** @var Reports $login */
        $login->openReports('Oro\Bundle\ReportBundle')
            ->filterBy('Name', $reportName)
            ->open(array($reportName))
            ->filterBy('Username', $userName)
            ->entityExists(array($userName));
        /** @var ConfigEntities $login */
        $login->openConfigEntities('Oro\Bundle\EntityConfigBundle')
            ->filterBy('Name', 'User', 'is equal to')
            ->open(['User'])
            ->edit()
            ->enableNotes('No')
            ->save()
            ->assertMessage('Entity saved');

        return $reportName;
    }

    /**
     * Test to check that report will not show entities with disabled activities
     * @depends testActivityReport
     * @param $reportName
     */
    public function testReportForDisabledActivity($reportName)
    {
        $this->markTestSkipped('Test skipped due to bug BAP-8967');
        $login = $this->login();

        /** @var Reports $login */
        $login->openReports('Oro\Bundle\ReportBundle')
            ->filterBy('Name', $reportName)
            ->open(array($reportName))
            ->assertNoDataMessage('No records found');
    }
}
