<?php

namespace Oro\Bundle\SidebarBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class WidgetTest extends WebTestCase
{
    private array $widget = [
        'position' => 0,
        'widgetName' => 'hello_world',
        'settings' => [
            'content' => 'Welcome to OroCRM!<br/>OroCRM is an easy-to-use, open source CRM with built-in marketing tools
 for your ecommerce business. learn more at <a href=\"http://orocrm.com\">orocrm.com</a>'
        ]
    ];

    protected function setUp(): void
    {
        $this->initClient([], self::generateWsseAuthHeader());
    }

    /**
     * @dataProvider positionsPostProvider
     */
    public function testGetInitialWidget(array $position)
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_sidebarwidgets', ['placement' => $position['placement']])
        );
        $result = self::getJsonResponseContent($this->client->getResponse(), 200);
        self::assertEmpty($result);
    }

    /**
     * @depends testGetInitialWidget
     * @dataProvider positionsPostProvider
     */
    public function testPostWidget(array $position)
    {
        $this->client->jsonRequest(
            'POST',
            $this->getUrl('oro_api_post_sidebarwidgets'),
            $position
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 201);
        self::assertGreaterThan(0, $result['id']);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_sidebarwidgets', ['placement' => $position['placement']])
        );

        $actualResult = self::getJsonResponseContent($this->client->getResponse(), 200);
        self::assertEquals(array_merge($result, $position), reset($actualResult));
    }

    /**
     * @depends testPostWidget
     * @dataProvider positionsPutProvider
     */
    public function testPutWidget(array $position)
    {
        // get sidebar id
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_sidebarwidgets', ['placement' => $position['placement']])
        );

        $actualResult = self::getJsonResponseContent($this->client->getResponse(), 200);
        $position = array_merge(['id' => reset($actualResult)['id']], $position);
        self::assertNotEquals($position, $actualResult);

        $this->client->jsonRequest(
            'PUT',
            $this->getUrl('oro_api_put_sidebarwidgets', ['widgetId' =>  $position['id']]),
            $position
        );

        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 200);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_sidebarwidgets', ['placement' => $position['placement']])
        );

        $actualResult = self::getJsonResponseContent($this->client->getResponse(), 200);

        self::assertEquals($position, reset($actualResult));
    }

    /**
     * @depends testPostWidget
     * @dataProvider positionsPostProvider
     */
    public function testDelete(array $position)
    {
        // get sidebar widget id
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_sidebarwidgets', ['placement' => $position['placement']])
        );

        $actualResult = self::getJsonResponseContent($this->client->getResponse(), 200);
        $position = array_merge(['id' => reset($actualResult)['id']], $position);

        // delete sidebar widget by id
        $this->client->jsonRequest(
            'DELETE',
            $this->getUrl('oro_api_delete_sidebarwidgets', ['widgetId' => $position['id']])
        );
        self::assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);

        // get sidebar widget
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_sidebarwidgets', ['placement' => $position['placement']])
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);
        self::assertEmpty($result);
    }

    public function positionsPostProvider(): array
    {
        return [
            [
                'left-maximized' => array_merge(
                    ['placement' => 'left'],
                    $this->widget,
                    ['state' => 'SIDEBAR_MINIMIZED']
                )
            ],
            [
                'right-maximized' => array_merge(
                    ['placement' => 'right'],
                    $this->widget,
                    ['state' => 'SIDEBAR_MINIMIZED']
                )
            ]
        ];
    }

    public function positionsPutProvider(): array
    {
        return [
            [
                'left-minimized' => array_merge(
                    ['placement' => 'left'],
                    $this->widget,
                    ['state' => 'SIDEBAR_MINIMIZED']
                )
            ],
            [
                'left-maximized' => array_merge(
                    ['placement' => 'left'],
                    $this->widget,
                    ['state' => 'SIDEBAR_MAXIMIZED']
                )
            ],
            [
                'right-minimized' => array_merge(
                    ['placement' => 'right'],
                    $this->widget,
                    ['state' => 'SIDEBAR_MINIMIZED']
                )
            ],
            [
                'right-maximized' => array_merge(
                    ['placement' => 'right'],
                    $this->widget,
                    ['state' => 'SIDEBAR_MAXIMIZED']
                )
            ],
        ];
    }
}
