<?php

namespace Oro\Bundle\ReportBundle\Tests\Functional;

use Oro\Bundle\DataGridBundle\Async\Topics;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

class ControllersTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp()
    {
        $this->initClient(
            [],
            array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1])
        );
        $this->client->useHashNavigation(true);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_report_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('Manage Custom Reports - Reports & Segments', $crawler->filter('#page-title')->html());
    }

    /**
     * @param array $report
     * @dataProvider reportDataProvider
     */
    public function testCreate($report)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_report_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $form = $this->fillForm($form, $report);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Report saved", $crawler->html());
    }

    /**
     * @depends testCreate
     * @dataProvider reportDataProvider
     *
     * @param array $report
     * @param array $reportResult
     */
    public function testView(array $report, array $reportResult)
    {
        $response = $this->client->requestGrid(
            'reports-grid',
            ['reports-grid[_filter][name][value]' => $report['oro_report_form[name]'], ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];
        $this->client->request('GET', $this->getUrl('oro_report_view', ['id' => $id]));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $response = $this->client->requestGrid(
            Report::GRID_PREFIX . $id,
            []
        );

        $result = $this->getJsonResponseContent($response, 200);
        $data = $result['data'];

        for ($i = 0; $i < count($data); $i++) {
            $reportResult[$i]['id'] = $data[$i]['id'];
            $reportResult[$i]['view_link'] = $this->getUrl('oro_user_view', ['id' => $data[$i]['id']]);
        }

        $options = $result['options'];
        $this->assertReportRecordsEquals($reportResult, $data, $options['totalRecords']);
    }

    /**
     * @param array $report
     * @param array $reportResult
     * @depends testView
     * @dataProvider reportDataProvider
     */
    public function testUpdate(array $report, array $reportResult)
    {
        $response = $this->client->requestGrid(
            'reports-grid',
            ['reports-grid[_filter][name][value]' => $report['oro_report_form[name]']]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];

        $crawler = $this->client->request('GET', $this->getUrl('oro_report_update', ['id' => $id]));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $report['oro_report_form[name]'] .= '_updated';
        $form = $this->fillForm($form, $report);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Report saved", $crawler->html());

        $response = $this->client->requestGrid(
            Report::GRID_PREFIX . $id,
            []
        );

        $result = $this->getJsonResponseContent($response, 200);

        $data = $result['data'];
        $options = $result['options'];

        for ($i = 0; $i < count($data); $i++) {
            $reportResult[$i]['id'] = $data[$i]['id'];
            $reportResult[$i]['view_link'] = $this->getUrl('oro_user_view', ['id' => $data[$i]['id']]);
        }

        $this->assertReportRecordsEquals($reportResult, $data, (int)$options['totalRecords']);
    }

    public function testExport()
    {
        $response = $this->client->requestGrid(
            'reports-grid',
            ['reports-grid[_filter][name][value]' => 'Admin_updated']
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_datagrid_export_action',
                ['gridName' => Report::GRID_PREFIX . $id, 'format' => 'csv']
            ),
            [],
            [],
            $this->generateNoHashNavigationHeader()
        );

        $response = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $response);
        $this->assertTrue($response['successful']);

        $this->assertMessageSent(
            Topics::PRE_EXPORT,
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
     * @param array $report
     * @depends testView
     * @dataProvider reportDataProvider
     */
    public function testDelete(array $report)
    {
        $response = $this->client->requestGrid(
            'reports-grid',
            ['reports-grid[_filter][name][value]' => $report['oro_report_form[name]'] . '_updated']
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_report', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('oro_report_update', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }

    public function testViewFromGrid()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_report_view_grid', ['gridName' => 'reports-grid']));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('reports-grid', $crawler->html());
        $this->assertEquals('reports-grid', $crawler->filter('h1.oro-subtitle')->html());
    }

    /**
     * Data provider for SOAP API tests
     *
     * @return array
     */
    public function reportDataProvider()
    {
        return $this->getApiRequestsData(__DIR__ . DIRECTORY_SEPARATOR . 'reports');
    }

    /**
     * @param Form $form
     * @param array $fields
     *
     * @return Form $form
     */
    protected function fillForm($form, $fields)
    {
        foreach ($fields as $fieldName => $value) {
            $form[$fieldName] = $value;
        }

        return $form;
    }

    /**
     * @param array $expected
     * @param array $actual
     * @param int $totalCount
     */
    protected function assertReportRecordsEquals(array $expected, array $actual, $totalCount)
    {
        $this->assertEquals(count($expected), $totalCount);
        for ($i = 0; $i < $totalCount; $i++) {
            // unset datagrid system info
            unset($actual[$i]['action_configuration']);
            //compare by value
            $this->assertEquals(array_values($expected[$i]), array_values($actual[$i]));
        }
    }
}
