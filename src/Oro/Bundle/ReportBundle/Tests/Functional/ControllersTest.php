<?php

namespace Oro\Bundle\ReportBundle\Tests\Functional;

use Oro\Bundle\DataGridBundle\Async\Topic\DatagridPreExportTopic;
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

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_report_index'));
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertEquals('Manage Custom Reports - Reports & Segments', $crawler->filter('#page-title')->html());
    }

    /**
     * @param array $report
     * @dataProvider reportDataProvider
     */
    public function testCreate(array $report): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_report_create'));
        $form = $this->getReportForm($crawler);
        $form = $this->fillForm($form, $report);
        $this->client->followRedirects();
        $crawler = $this->client->submit($form);
        $this->assertReportSaved($crawler);
    }

    /**
     * @depends testCreate
     * @dataProvider reportDataProvider
     */
    public function testView(array $report, array $reportResult): void
    {
        $id = $this->getReportId($report['oro_report_form[name]']);

        $this->client->request('GET', $this->getUrl('oro_report_view', ['id' => $id]));
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        $response = $this->client->requestGrid(Report::GRID_PREFIX . $id);
        $result = self::getJsonResponseContent($response, 200);
        $data = $result['data'];

        $num = count($data);
        for ($i = 0; $i < $num; $i++) {
            $reportResult[$i]['id'] = $data[$i]['id'];
            $reportResult[$i]['view_link'] = $this->getUrl('oro_user_view', ['id' => $data[$i]['id']]);
        }

        $this->assertReportRecordsEquals($reportResult, $data, $result['options']['totalRecords']);
    }

    /**
     * @depends testView
     * @dataProvider reportDataProvider
     */
    public function testUpdate(array $report, array $reportResult): void
    {
        $id = $this->getReportId($report['oro_report_form[name]']);

        $report['oro_report_form[name]'] .= '_updated';
        $crawler = $this->client->request('GET', $this->getUrl('oro_report_update', ['id' => $id]));
        $form = $this->getReportForm($crawler);
        $form = $this->fillForm($form, $report);
        $this->client->followRedirects();
        $crawler = $this->client->submit($form);
        $this->assertReportSaved($crawler);

        $response = $this->client->requestGrid(
            Report::GRID_PREFIX . $id,
            []
        );
        $result = self::getJsonResponseContent($response, 200);
        $data = $result['data'];

        $num = count($data);
        for ($i = 0; $i < $num; $i++) {
            $reportResult[$i]['id'] = $data[$i]['id'];
            $reportResult[$i]['view_link'] = $this->getUrl('oro_user_view', ['id' => $data[$i]['id']]);
        }

        $this->assertReportRecordsEquals($reportResult, $data, (int)$result['options']['totalRecords']);
    }

    public function testExport(): void
    {
        $id = $this->getReportId('Admin_updated');

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_datagrid_export_action',
                ['gridName' => Report::GRID_PREFIX . $id, 'format' => 'csv']
            ),
            [],
            [],
            self::generateNoHashNavigationHeader()
        );

        $response = self::getJsonResponseContent($this->client->getResponse(), 200);
        self::assertCount(1, $response);
        self::assertTrue($response['successful']);

        self::assertMessageSent(
            DatagridPreExportTopic::getName(),
            [
                'format' => 'csv',
                'notificationTemplate' => 'datagrid_export_result',
                'parameters' => [
                    'gridName' => sprintf('oro_report_table_%s', $id),
                    'gridParameters' => [],
                    'format_type' => 'excel'
                ],
            ]
        );
    }

    /**
     * @depends testView
     * @dataProvider reportDataProvider
     */
    public function testDelete(array $report): void
    {
        $id = $this->getReportId($report['oro_report_form[name]'] . '_updated');

        $this->ajaxRequest(
            'DELETE',
            $this->getUrl('oro_api_delete_report', ['id' => $id])
        );
        $result = $this->client->getResponse();
        self::assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('oro_report_update', ['id' => $id]));
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 404);
    }

    public function testViewFromGrid(): void
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_report_view_grid', ['gridName' => 'reports-grid'])
        );
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('reports-grid', $crawler->html());
        $this->assertEquals('reports-grid', $crawler->filter('h1.oro-subtitle')->html());
    }

    /**
     * Data provider for SOAP API tests
     *
     * @return array
     */
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

    private function assertReportRecordsEquals(array $expected, array $actual, int $totalCount): void
    {
        self::assertEquals(count($expected), $totalCount);
        for ($i = 0; $i < $totalCount; $i++) {
            // unset datagrid system info
            unset($actual[$i]['action_configuration']);
            //compare by value
            self::assertEquals(array_values($expected[$i]), array_values($actual[$i]));
        }
    }

    private function assertReportSaved(Crawler $crawler): void
    {
        $response = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($response, 200);
        self::assertStringContainsString('Report saved', $crawler->html());
    }
}
