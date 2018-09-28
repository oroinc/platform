<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional;

use Oro\Bundle\DataGridBundle\Async\Topics;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\SegmentBundle\Entity\Segment;
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
            ['oro_segments-grid[_filter][name][value]' => $report['oro_segment_form[name]'], ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];
        $this->client->request('GET', $this->getUrl('oro_segment_view', ['id' => $id]));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        if ($report['oro_segment_form[type]'] == 'static') {
            $this->client->request(
                'POST',
                $this->getUrl('oro_api_post_segment_run', ['id' => $id])
            );
            $result = $this->client->getResponse();
            $this->assertEmptyResponseStatusCodeEquals($result, 204);
        }

        $response = $this->client->requestGrid(Segment::GRID_PREFIX . $id);

        $result = $this->getJsonResponseContent($response, 200);
        $data = $result['data'];
        $options = $result['options'];
        $this->addOroDefaultPrefixToUrlInParameterArray($reportResult, 'view_link');
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
            ['oro_segments-grid[_filter][name][value]' => $report['oro_segment_form[name]']]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];

        $crawler = $this->client->request('GET', $this->getUrl('oro_segment_update', ['id' => $id]));
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
                $this->getUrl('oro_api_post_segment_run', ['id' => $id])
            );
            $result = $this->client->getResponse();
            $this->assertEmptyResponseStatusCodeEquals($result, 204);
        }

        $response = $response = $this->client->requestGrid(Segment::GRID_PREFIX . $id);

        $result = $this->getJsonResponseContent($response, 200);
        $data = $result['data'];
        $options = $result['options'];
        $this->addOroDefaultPrefixToUrlInParameterArray($reportResult, 'view_link');
        $this->verifyReport($reportResult, $data, (int)$options['totalRecords']);
    }

    public function testExport()
    {
        $response = $this->client->requestGrid(
            'oro_segments-grid',
            ['oro_segments-grid[_filter][name][value]' => 'Users Filterd Dynamic_updated']
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];

        $keys = ['oro_segment_grid_$id[_filter][c1][value]', 'oro_segment_grid_$id[_filter][c1][type]'];
        $filter = ['321admin123', 1];
        $keys = str_replace('$id', $id, $keys);
        $filter = array_combine($keys, $filter);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_datagrid_export_action',
                array_merge(
                    ['gridName' => Segment::GRID_PREFIX . $id, 'format' => 'csv'],
                    $filter
                )
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
                'parameters' => [
                    'gridName' => 'oro_segment_grid_' . $id,
                    'gridParameters' => [
                        '_filter' => [
                            'c1' => [
                                'value' => '321admin123',
                                'type' => '1'
                            ],
                        ],
                    ],
                    'format_type' => 'excel'
                ],
                'notificationTemplate' => 'datagrid_export_result',
            ]
        );
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
            ['oro_segments-grid[_filter][name][value]' => $report['oro_segment_form[name]'] . '_updated']
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_segment', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('oro_segment_update', ['id' => $id]));

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
