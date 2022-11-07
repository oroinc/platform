<?php

namespace Oro\Bundle\SidebarBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SidebarTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateWsseAuthHeader());
    }

    /**
     * @dataProvider positionsPostProvider
     */
    public function testGetInitialPositions(array $position)
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_sidebars', ['position' => $position['position']])
        );
        $result = $this->client->getResponse();
        self::assertEmptyResponseStatusCodeEquals($result, 204);
        $this->assertEmpty($result->getContent());
    }

    /**
     * @depends testGetInitialPositions
     * @dataProvider positionsPostProvider
     */
    public function testPostPosition(array $position)
    {
        $this->client->jsonRequest(
            'POST',
            $this->getUrl('oro_api_post_sidebars'),
            $position
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 201);
        $this->assertGreaterThan(0, $result['id']);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_sidebars', ['position' => $position['position']])
        );

        $actualResult = self::getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEquals(array_merge($result, $position), $actualResult);
    }

    /**
     * @depends testPostPosition
     * @dataProvider positionsPutProvider
     */
    public function testPutPositions(array $position)
    {
        // get sidebar id
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_sidebars', ['position' => $position['position']])
        );

        $actualResult = self::getJsonResponseContent($this->client->getResponse(), 200);
        $position = array_merge(['id' => $actualResult['id']], $position);
        $this->assertNotEquals($position, $actualResult);

        $this->client->jsonRequest(
            'PUT',
            $this->getUrl('oro_api_put_sidebars', ['stateId' => $position['id']]),
            $position
        );

        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 200);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_sidebars', ['position' => $position['position']])
        );

        $actualResult = self::getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals($position, $actualResult);
    }

    public function positionsPostProvider(): array
    {
        return [
            [
                'left-maximized' => [
                    'position' => 'SIDEBAR_LEFT',
                    'state'    => 'SIDEBAR_MAXIMIZED'
                ]
            ],
            [
                'right-maximized' => [
                    'position' => 'SIDEBAR_RIGHT',
                    'state'    => 'SIDEBAR_MAXIMIZED'
                ]
            ]
        ];
    }

    public function positionsPutProvider(): array
    {
        return [
            [
                'left-minimized' => [
                    'position' => 'SIDEBAR_LEFT',
                    'state'    => 'SIDEBAR_MINIMIZED'
                ]
            ],
            [
                'left-maximized' => [
                    'position' => 'SIDEBAR_LEFT',
                    'state'    => 'SIDEBAR_MAXIMIZED'
                ]
            ],
            [
                'right-minimized' => [
                    'position' => 'SIDEBAR_RIGHT',
                    'state'    => 'SIDEBAR_MINIMIZED'
                ]
            ],
            [
                'right-maximized' => [
                    'position' => 'SIDEBAR_RIGHT',
                    'state'    => 'SIDEBAR_MAXIMIZED'
                ]
            ],
        ];
    }
}
