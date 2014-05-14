<?php

namespace Oro\Bundle\SidebarBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class WidgetTest extends WebTestCase
{
    /** @var array  */
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
        // TODO: Implement position tests
        $this->client = self::createClient(array(), $this->generateWsseAuthHeader());
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
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEmpty($result);
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

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $this->assertGreaterThan(0, $result['id']);

        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_sidebarwidgets', array('placement' => $position['placement']))
        );

        $actualResult = $this->getJsonResponseContent($this->client->getResponse(), 200);
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

        $actualResult = $this->getJsonResponseContent($this->client->getResponse(), 200);
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
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_sidebarwidgets', array('placement' => $position['placement']))
        );

        $actualResult = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals($position, reset($actualResult));
    }

    /**
     * @depends testPostWidget
     * @dataProvider positionsPostProvider
     */
    public function testDelete($position)
    {
        // get sidebar widget id
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_sidebarwidgets', array('placement' => $position['placement']))
        );

        $actualResult = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $position = array_merge(array('id' => reset($actualResult)['id']), $position);

        // delete sidebar widget by id
        $this->client->request(
            'DELETE',
            $this->client->generate('oro_api_delete_sidebarwidgets', array('widgetId' => $position['id']))
        );
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 204);

        // get sidebar widget
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_sidebarwidgets', array('placement' => $position['placement']))
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEmpty($result);
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
