<?php

namespace Oro\Bundle\TagBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class TaggableControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
    }

    public function testPostAction()
    {
        $url = $this->getUrl(
            'oro_api_post_taggable',
            ['entity' => 'user', 'entityId' => 1]
        );
        $this->client->request(
            'POST',
            $url,
            [
                'tags' => [
                    ['name' => 'tag1'],
                    ['name' => 'tag2']
                ]
            ]
        );
        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
    }
}
