<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\SegmentBundle\Entity\Segment;
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
        $crawler = $this->client->request('GET', $this->getUrl('oro_segment_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('Manage Segments - Reports & Segments', $crawler->filter('#page-title')->html());
    }

    /**
     * @param array $report
     * @dataProvider segmentsDataProvider
     */
    public function testCreate(array $report)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_segment_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $form = $this->fillForm($form, $report);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertContains('Segment saved', $crawler->html());
    }

    /**
     * @depends testCreate
     * @dataProvider segmentsDataProvider
     */
    public function testView(array $report, array $reportResult)
    {
        $response = $this->client->requestGrid(
            'oro_segments-grid',
            array('oro_segments-grid[_filter][name][value]' => $report['oro_segment_form[name]'],)
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];
        $this->client->request('GET', $this->getUrl('oro_segment_view', array('id' => $id)));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        if ($report['oro_segment_form[type]'] == 'static') {
            $this->client->request(
                'POST',
                $this->getUrl('oro_api_post_segment_run', array('id' => $id))
            );
            $result = $this->client->getResponse();
            $this->assertEmptyResponseStatusCodeEquals($result, 204);
        }

        $response = $this->client->requestGrid(Segment::GRID_PREFIX . $id);

        $result = $this->getJsonResponseContent($response, 200);
        $data = $result['data'];
        $options = $result['options'];
        $this->verifyReport($reportResult, $data, $options['totalRecords']);
    }

    /**
     * @param array $report
     * @param array $reportResult
     * @depends testView
     * @dataProvider segmentsDataProvider
     */
    public function testUpdate(array $report, array $reportResult)
    {
        $response = $this->client->requestGrid(
            'oro_segments-grid',
            array('oro_segments-grid[_filter][name][value]' => $report['oro_segment_form[name]'])
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];

        $crawler = $this->client->request('GET', $this->getUrl('oro_segment_update', array('id' => $id)));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $report['oro_segment_form[name]'] .= '_updated';
        $form = $this->fillForm($form, $report);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Segment saved", $crawler->html());

        if ($report['oro_segment_form[type]'] == 'static') {
            $this->client->request(
                'POST',
                $this->getUrl('oro_api_post_segment_run', array('id' => $id))
            );
            $result = $this->client->getResponse();
            $this->assertEmptyResponseStatusCodeEquals($result, 204);
        }

        $response = $response = $this->client->requestGrid(Segment::GRID_PREFIX . $id);

        $result = $this->getJsonResponseContent($response, 200);
        $data = $result['data'];
        $options = $result['options'];
        $this->verifyReport($reportResult, $data, (int)$options['totalRecords']);
    }

    /**
     * @param array $report
     * @param array $reportResult
     * @param array $segmentExport
     * @param array|null $segmentExportFilter
     *
     * @depends testView
     * @dataProvider segmentsDataProvider
     */
    public function testExport(array $report, array $reportResult, array $segmentExport, $segmentExportFilter)
    {
        $response = $this->client->requestGrid(
            'oro_segments-grid',
            array('oro_segments-grid[_filter][name][value]' => $report['oro_segment_form[name]'] . '_updated')
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];

        $keys = array_keys($segmentExportFilter);
        $filter = array_values($segmentExportFilter);
        $keys = str_replace('$id', $id, $keys);
        $filter = array_combine($keys, $filter);

        //capture output content
        ob_start();
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_datagrid_export_action',
                array_merge(
                    array('gridName' => Segment::GRID_PREFIX . $id, "format" => 'csv'),
                    $filter
                )
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

        //file to array
        $content = str_getcsv($content, "\n", '"', '"');
        //remove headers
        unset($content[0]);
        $content = array_values($content);
        //row to array
        foreach ($content as &$row) {
            $row = str_getcsv($row, ',', '"', '"');
        }
        $this->verifyReport($segmentExport, $content, count($content));
    }

    /**
     * @param array $report
     * @depends testView
     * @dataProvider segmentsDataProvider
     */
    public function testDelete(array $report)
    {
        $response = $this->client->requestGrid(
            'oro_segments-grid',
            array('oro_segments-grid[_filter][name][value]' => $report['oro_segment_form[name]'] . '_updated')
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_segment', array('id' => $id))
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('oro_segment_update', array('id' => $id)));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }

    /**
     * Data provider for SOAP API tests
     *
     * @return array
     */
    public function segmentsDataProvider()
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
            // unset datagrid system info
            unset($actual[$i]['action_configuration']);
            //compare by value
            $this->assertEquals(array_values($expected[$i]), array_values($actual[$i]));
        }
        return true;
    }
}
