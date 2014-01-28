<?php

namespace Oro\Bundle\ReportBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Symfony\Component\DomCrawler\Form;

/**
 * @outputBuffering enabled
 * @db_isolation
 * @db_reindex
 */
class ControllersTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient(
            array(),
            array_merge(ToolsAPI::generateBasicHeader(), array('HTTP_X-CSRF-Header' => 1))
        );
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->client->generate('oro_report_index'));
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertEquals('Manage Custom Reports - Reports', $crawler->filter('#page-title')->html());
    }

    /**
     * @param array $report
     * @dataProvider requestsApi()
     */
    public function testCreate($report)
    {
        $crawler = $this->client->request('GET', $this->client->generate('oro_report_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $form = $this->fillForm($form, $report);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains("Report saved", $crawler->html());
    }

    /**
     * @depends testCreate
     * @dataProvider requestsApi()
     */
    public function testView($report, $reportResult)
    {
        $result = ToolsAPI::getEntityGrid(
            $this->client,
            'reports-grid',
            array(
                'reports-grid[_filter][name][value]' => $report['oro_report_form[name]'],
            )
        );

        ToolsAPI::assertJsonResponse($result, 200);

        $result = ToolsAPI::jsonToArray($result->getContent());
        $result = reset($result['data']);
        $id = $result['id'];
        $this->client->request('GET', $this->client->generate('oro_report_view', array('id' => $id)));
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');

        $result = ToolsAPI::getEntityGrid(
            $this->client,
            "oro_report_table_{$id}",
            array(
            )
        );
        $result = ToolsAPI::jsonToArray($result->getContent());
        $data = $result['data'];
        $options = $result['options'];
        $this->verifyReport($reportResult, $data, $options['totalRecords']);
    }

    /**
     * @param array $report
     * @param $reportResult
     * @depends testView
     * @dataProvider requestsApi()
     */
    public function testUpdate($report, $reportResult)
    {
        $result = ToolsAPI::getEntityGrid(
            $this->client,
            'reports-grid',
            array(
                'reports-grid[_filter][name][value]' => $report['oro_report_form[name]'],
            )
        );

        ToolsAPI::assertJsonResponse($result, 200);

        $result = ToolsAPI::jsonToArray($result->getContent());
        $result = reset($result['data']);
        $id = $result['id'];

        $crawler = $this->client->request('GET', $this->client->generate('oro_report_update', array('id' => $id)));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $report['oro_report_form[name]'] .= '_updated';
        $form = $this->fillForm($form, $report);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains("Report saved", $crawler->html());

        $result = ToolsAPI::getEntityGrid(
            $this->client,
            "oro_report_table_{$id}",
            array(
            )
        );
        $result = ToolsAPI::jsonToArray($result->getContent());
        $data = $result['data'];
        $options = $result['options'];
        $this->verifyReport($reportResult, $data, (int)$options['totalRecords']);
    }

    /**
     * @param array $report
     * @param array $reportResult
     *
     * @depends testView
     * @dataProvider requestsApi()
     */
    public function testExport($report, $reportResult)
    {
        $result = ToolsAPI::getEntityGrid(
            $this->client,
            'reports-grid',
            array(
                'reports-grid[_filter][name][value]' => $report['oro_report_form[name]'] . '_updated',
            )
        );

        ToolsAPI::assertJsonResponse($result, 200);

        $result = ToolsAPI::jsonToArray($result->getContent());
        $result = reset($result['data']);
        $id = $result['id'];

        //capture output content
        ob_start();
        $this->client->request(
            'GET',
            $this->client->generate(
                'oro_datagrid_export_action',
                array('gridName' =>"oro_report_table_{$id}", "format" => 'csv')
            )
        );
        $content = ob_get_contents();
        // Clean the output buffer and end it
        ob_end_clean();

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/csv; charset=UTF-8');

        //file to array
        $content = str_getcsv($content, "\n", '"', '"');
        //remove headers
        unset($content[0]);
        $content = array_values($content);
        //row to array
        foreach ($content as &$row) {
            $row = str_getcsv($row, ',', '"', '"');
        }
        $this->verifyReport($reportResult, $content, count($content));
    }
        /**
     * @param array $report
     * @depends testView
     * @dataProvider requestsApi()
     */
    public function testDelete($report)
    {
        $result = ToolsAPI::getEntityGrid(
            $this->client,
            'reports-grid',
            array(
                'reports-grid[_filter][name][value]' => $report['oro_report_form[name]'] . '_updated',
            )
        );

        ToolsAPI::assertJsonResponse($result, 200);

        $result = ToolsAPI::jsonToArray($result->getContent());
        $result = reset($result['data']);
        $id = $result['id'];

        $this->client->request(
            'DELETE',
            $this->client->generate('oro_api_delete_report', array('id' => $id))
        );

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 204);

        $this->client->request('GET', $this->client->generate('oro_report_update', array('id' => $id)));

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 404, 'text/html; charset=UTF-8');
    }

    /**
     * Data provider for SOAP API tests
     *
     * @return array
     */
    public function requestsApi()
    {
        return ToolsAPI::requestsApi(__DIR__ . DIRECTORY_SEPARATOR . 'reports');
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
     * @param $expected
     * @param $actual
     * @param $totalCount
     *
     * @return bool
     */
    protected function verifyReport($expected, $actual, $totalCount)
    {
        $this->assertEquals(count($expected), $totalCount);
        for ($i = 0; $i < $totalCount; $i++) {
            //compare by value
            $this->assertEquals(array_values($expected[$i]), array_values($actual[$i]));
        }
        return true;
    }
}
