<?php

namespace Oro\Bundle\ReportBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ControllersTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            array(),
            array_merge($this->generateBasicAuthHeader(), array('HTTP_X-CSRF-Header' => 1))
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
     */
    public function testView(array $report, array $reportResult)
    {
        $response = $this->client->requestGrid(
            'reports-grid',
            array('reports-grid[_filter][name][value]' => $report['oro_report_form[name]'],)
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];
        $this->client->request('GET', $this->getUrl('oro_report_view', array('id' => $id)));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $response = $this->client->requestGrid(
            Report::GRID_PREFIX . $id,
            array()
        );

        $result = $this->getJsonResponseContent($response, 200);
        $data = $result['data'];

        for ($i = 0; $i < count($data); $i++) {
            $reportResult[$i]['id'] = $data[$i]['id'];
            $reportResult[$i]['view_link'] = $this->getUrl('oro_user_view', array('id' => $data[$i]['id']));
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
            array('reports-grid[_filter][name][value]' => $report['oro_report_form[name]'])
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];

        $crawler = $this->client->request('GET', $this->getUrl('oro_report_update', array('id' => $id)));
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
            array()
        );

        $result = $this->getJsonResponseContent($response, 200);

        $data = $result['data'];
        $options = $result['options'];

        for ($i = 0; $i < count($data); $i++) {
            $reportResult[$i]['id'] = $data[$i]['id'];
            $reportResult[$i]['view_link'] = $this->getUrl('oro_user_view', array('id' => $data[$i]['id']));
        }

        $this->assertReportRecordsEquals($reportResult, $data, (int)$options['totalRecords']);
    }

    /**
     * @param array $report
     * @param array $reportResult
     *
     * @depends testView
     * @dataProvider reportDataProvider
     */
    public function testExport(array $report, array $reportResult)
    {
        $response = $this->client->requestGrid(
            'reports-grid',
            array('reports-grid[_filter][name][value]' => $report['oro_report_form[name]'] . '_updated')
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];

        //capture output content
        ob_start();
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_datagrid_export_action',
                array('gridName' => Report::GRID_PREFIX . $id, 'format' => 'csv')
            ),
            [],
            [],
            $this->generateNoHashNavigationHeader()
        );
        $content = ob_get_contents();
        // Clean the output buffer and end it
        ob_end_clean();

        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, 200);
        $this->assertResponseContentTypeEquals($result, 'text/csv; charset=UTF-8');
        $this->assertEquals('binary', $result->headers->get('Content-Transfer-Encoding'));
        $this->assertStringStartsWith(
            'attachment; filename="datagrid_' . Report::GRID_PREFIX . $id,
            $result->headers->get('Content-Disposition')
        );

        //file to array
        $content = str_getcsv($content, "\n", '"', '"');
        //remove headers
        unset($content[0]);
        $content = array_values($content);
        //row to array
        foreach ($content as &$row) {
            $row = str_getcsv($row, ',', '"', '"');
        }
        $this->assertReportRecordsEquals($reportResult, $content, count($content));
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
            array('reports-grid[_filter][name][value]' => $report['oro_report_form[name]'] . '_updated')
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_report', array('id' => $id))
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('oro_report_update', array('id' => $id)));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
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
