<?php

namespace Oro\Bundle\ReportBundle\Tests\Functional;

use Oro\Bundle\DataGridBundle\Tests\Functional\Environment\DatagridQueryCollector;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class ControllersTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader()
        );
        $this->client->useHashNavigation(true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->getDatagridQueryCollector()->disable();
    }

    public function reportDataProvider(): array
    {
        return self::getApiRequestsData(__DIR__ . DIRECTORY_SEPARATOR . 'reports');
    }

    /**
     * test that both the count and the main queries are executed by datagrid query executor
     */
    public function testReportQueries(): void
    {
        $report = [
            'oro_report_form[name]'       => 'Test',
            'oro_report_form[type]'       => 'TABLE',
            'oro_report_form[owner]'      => 1,
            'oro_report_form[entity]'     => User::class,
            'oro_report_form[definition]' => '{'
                . '"columns":[{"name":"username","label":"Username","func":null,"sorting":"ASC"}],'
                . '"grouping_columns":[],'
                . '"filters":[]'
                . '}'
        ];

        $crawler = $this->client->request('GET', $this->getUrl('oro_report_create'));
        $form = $this->getReportForm($crawler);
        $form = $this->fillForm($form, $report);
        $this->client->followRedirects();
        $crawler = $this->client->submit($form);
        $this->assertReportSaved($crawler);

        $reportDatagridName = Report::GRID_PREFIX . $this->getReportId($report['oro_report_form[name]']);
        $this->getDatagridQueryCollector()->enable($reportDatagridName);

        $response = $this->client->requestGrid($reportDatagridName);
        self::assertJsonResponseStatusCodeEquals($response, 200);

        $this->assertEquals(
            [
                $reportDatagridName => [
                    // base query to calculate count of records
                    'SELECT t1.id FROM Oro\Bundle\UserBundle\Entity\User t1',
                    // main query
                    'SELECT t1.username as c1,'
                    . ' t1.id, IDENTITY(t1.organization) AS t1_organization_id,'
                    . ' IDENTITY(t1.owner) AS t1_owner_id'
                    . ' FROM Oro\Bundle\UserBundle\Entity\User t1'
                    . ' ORDER BY c1 ASC'
                ]
            ],
            $this->getDatagridQueryCollector()->getExecutedQueries()
        );
    }

    private function getDatagridQueryCollector(): DatagridQueryCollector
    {
        return self::getContainer()->get('oro_datagrid.tests.datagrid_orm_query_collector');
    }

    /**
     * @param Crawler $crawler
     *
     * @return Form
     */
    private function getReportForm(Crawler $crawler): Form
    {
        return $crawler->selectButton('Save and Close')->form();
    }

    private function fillForm(Form $form, array $fields): Form
    {
        foreach ($fields as $fieldName => $value) {
            $form[$fieldName] = $value;
        }

        return $form;
    }

    private function getReportId(string $reportName): int
    {
        $response = $this->client->requestGrid(
            'reports-grid',
            ['reports-grid[_filter][name][value]' => $reportName]
        );

        $responseContent = self::getJsonResponseContent($response, 200);
        $data = reset($responseContent['data']);

        return $data['id'];
    }

    private function assertReportSaved(Crawler $crawler): void
    {
        $response = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($response, 200);
        self::assertStringContainsString('Report saved', $crawler->html());
    }
}
