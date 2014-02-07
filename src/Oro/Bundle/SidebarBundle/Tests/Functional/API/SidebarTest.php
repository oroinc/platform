<?php

namespace Oro\Bundle\SidebarBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;

/**
 * @outputBuffering enabled
 * @db_isolation
 */
class SidebarTest extends WebTestCase
{
    /** @var Client  */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient(array(), ToolsAPI::generateWsseHeader());
    }

    /**
    /**
     * @dataProvider positionsPostProvider
     */
    public function testGetInitialPositions($position)
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_sidebars', array('position' => $position['position']))
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 204);
        $this->assertEmpty($result->getContent());
    }

    /**
     * @depends testGetInitialPositions
     * @dataProvider positionsPostProvider
     */
    public function testPostPosition($position)
    {
        $this->client->request(
            'POST',
            $this->client->generate('oro_api_post_sidebars'),
            array(),
            array(),
            array(),
            json_encode($position)
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 201);
        $result = ToolsAPI::jsonToArray($result->getContent());
        $this->assertGreaterThan(0, $result['id']);

        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_sidebars', array('position' => $position['position']))
        );

        $actualResult = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($actualResult, 200);
        $actualResult = ToolsAPI::jsonToArray($actualResult->getContent());
        $this->assertEquals(array_merge($result, $position), $actualResult);
    }

    /**
     * @depends testPostPosition
     * @dataProvider positionsPutProvider
     */
    public function testPutPositions($position)
    {
        // get sidebar id
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_sidebars', array('position' => $position['position']))
        );

        $actualResult = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($actualResult, 200);
        $actualResult = ToolsAPI::jsonToArray($actualResult->getContent());
        $position = array_merge(array('id' => $actualResult['id']), $position);
        $this->assertNotEquals($position, $actualResult);

        $this->client->request(
            'PUT',
            $this->client->generate('oro_api_put_sidebars', array('stateId' =>  $position['id'])),
            array(),
            array(),
            array(),
            json_encode($position)
        );

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);

        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_sidebars', array('position' => $position['position']))
        );

        $actualResult = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($actualResult, 200);
        $actualResult = ToolsAPI::jsonToArray($actualResult->getContent());

        $this->assertEquals($position, $actualResult);
    }

    public function positionsPostProvider()
    {
        return array(
            array(
          'left-maximized' => array(
              'position' => 'SIDEBAR_LEFT',
              'state' => 'SIDEBAR_MAXIMIZED'
          )),
            array(
            'right-maximized' => array(
                'position' => 'SIDEBAR_RIGHT',
                'state' => 'SIDEBAR_MAXIMIZED'
            ))
        );
    }
    public function positionsPutProvider()
    {
        return array(
            array(
                'left-minimized' => array(
                    'position' => 'SIDEBAR_LEFT',
                    'state' => 'SIDEBAR_MINIMIZED'
                )),
            array(
                'left-maximized' => array(
                    'position' => 'SIDEBAR_LEFT',
                    'state' => 'SIDEBAR_MAXIMIZED'
                )),
            array(
                'right-minimized' => array(
                    'position' => 'SIDEBAR_RIGHT',
                    'state' => 'SIDEBAR_MINIMIZED'
                )),
            array(
                'right-maximized' => array(
                    'position' => 'SIDEBAR_RIGHT',
                    'state' => 'SIDEBAR_MAXIMIZED'
                )),
        );
    }
}
