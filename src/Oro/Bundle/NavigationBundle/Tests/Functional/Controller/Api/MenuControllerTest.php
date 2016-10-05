<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Controller\Api;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class MenuControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testDeleteShouldFail()
    {
        $url = $this->getUrl('oro_api_delete_menuupdates', [
            'menuName' => 'foo',
            'key' => 'bar'
        ]);
        $this->client->request('DELETE', $url, [], [], $this->generateWsseAuthHeader());
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }
}
