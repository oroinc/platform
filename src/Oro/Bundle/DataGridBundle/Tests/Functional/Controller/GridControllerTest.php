<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Controller;

use Oro\Bundle\DataGridBundle\Async\Topic\DatagridPreExportTopic;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class GridControllerTest extends WebTestCase
{
    use MessageQueueAssertTrait;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testShouldSendExportMessage(): void
    {
        $this->client->request('GET', $this->getUrl('oro_datagrid_export_action', [
            'gridName' => 'audit-grid',
            'format' => 'csv',
            'audit-grid' => [
                '_pager' => [
                    '_page' => 1,
                    '_per_page' => 25,
                ],
                '_parameters' => ['view' => '__all__'],
                '_appearance' => ['_type' => 'grid'],
                '_sort_by' => ['name' => 'ASC'],
                '_columns' => 'organization1.fields1.audit1.user1.impersonation1',
            ],
        ]));

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);
        $this->assertTrue($result['successful']);

        self::assertMessageSent(DatagridPreExportTopic::getName(), [
            'outputFormat' => 'csv',
            'contextParameters' => [
                'gridName' => 'audit-grid',
                'gridParameters' => [
                    '_pager' => [
                        '_page' => '1',
                        '_per_page' => '25',
                    ],
                    '_parameters' => ['view' => '__all__'],
                    '_appearance' => ['_type' => 'grid'],
                    '_sort_by' => ['name' => 'ASC'],
                    '_columns' => 'organization1.fields1.audit1.user1.impersonation1',

                ],
                FormatterProvider::FORMAT_TYPE => 'excel'
            ],
        ]);
    }

    public function testMassActionActionWithToken(): void
    {
        $this->client->disableReboot();

        $this->ajaxRequest(
            'GET',
            $this->getUrl(
                'oro_datagrid_mass_action',
                [
                    'gridName' => 'grid',
                    'actionName' => 'action1'
                ]
            )
        );

        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 403);
        self::assertEquals('{}', $result->getContent());
    }
}
