<?php

namespace Oro\Bundle\SidebarBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;

/**
 * @outputBuffering enabled
 * @db_isolation
 */
class WidgetTest extends WebTestCase
{
    protected $widget = array(
        'position' => 0,
        'widgetName' => "hello_world",
        'settings' => array(
            'content' => 'Welcome to OroCRM!<br/>OroCRM is an easy-to-use, open source CRM with built-in marketing tools
 for your ecommerce business. learn more at <a href=\"http://orocrm.com\">orocrm.com</a>'
        )
    );

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
    public function testGetInitialWidget($position)
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_sidebarwidgets', array('placement' => $position['placement']))
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);
        $this->assertEmpty(ToolsAPI::jsonToArray($result->getContent()));
    }

    /**
     * @depends testGetInitialWidget
     * @dataProvider positionsPostProvider
     */
    public function testPostWidget($position)
    {
        $this->client->request(
            'POST',
            $this->client->generate('oro_api_post_sidebarwidgets'),
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
            $this->client->generate('oro_api_get_sidebarwidgets', array('placement' => $position['placement']))
        );

        $actualResult = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($actualResult, 200);
        $actualResult = ToolsAPI::jsonToArray($actualResult->getContent());
        $this->assertEquals(array_merge($result, $position), reset($actualResult));
    }

    /**
     * @depends testPostWidget
     * @dataProvider positionsPutProvider
     */
    public function testPutWidget($position)
    {
        // get sidebar id
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_sidebarwidgets', array('placement' => $position['placement']))
        );

        $actualResult = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($actualResult, 200);
        $actualResult = ToolsAPI::jsonToArray($actualResult->getContent());
        $position = array_merge(array('id' => reset($actualResult)['id']), $position);
        $this->assertNotEquals($position, $actualResult);

        $this->client->request(
            'PUT',
            $this->client->generate('oro_api_put_sidebarwidgets', array('widgetId' =>  $position['id'])),
            array(),
            array(),
            array(),
            json_encode($position)
        );

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);

        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_sidebarwidgets', array('placement' => $position['placement']))
        );

        $actualResult = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($actualResult, 200);
        $actualResult = ToolsAPI::jsonToArray($actualResult->getContent());

        $this->assertEquals($position, reset($actualResult));
    }

    public function positionsPostProvider()
    {
        return array(
            array(
          'left-maximized' => array_merge(
              array('placement' => 'left'),
              $this->widget,
              array('state' => 'SIDEBAR_MINIMIZED')
          )),
            array(
            'right-maximized' => array_merge(
                array('placement' => 'right'),
                $this->widget,
                array('state' => 'SIDEBAR_MINIMIZED')
            ))
        );
    }
    public function positionsPutProvider()
    {
        return array(
            array(
                'left-minimized' => array_merge(
                    array('placement' => 'left'),
                    $this->widget,
                    array('state' => 'SIDEBAR_MINIMIZED')
                )),
            array(
                'left-maximized' => array_merge(
                    array('placement' => 'left'),
                    $this->widget,
                    array('state' => 'SIDEBAR_MAXIMIZED')
                )),
            array(
                'right-minimized' => array_merge(
                    array('placement' => 'right'),
                    $this->widget,
                    array('state' => 'SIDEBAR_MINIMIZED')
                )),
            array(
                'right-maximized' => array_merge(
                    array('placement' => 'right'),
                    $this->widget,
                    array('state' => 'SIDEBAR_MAXIMIZED')
                )),
        );
    }
}
