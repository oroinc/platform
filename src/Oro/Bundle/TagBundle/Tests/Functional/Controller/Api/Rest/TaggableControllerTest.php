<?php

namespace Oro\Bundle\TagBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class TaggableControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
    }

    public function testPostAction()
    {
        $url = $this->getUrl(
            'oro_api_post_taggable',
            ['entity' => 'user', 'entityId' => 1]
        );
        $this->client->jsonRequest(
            'POST',
            $url,
            [
                'tags' => [
                    ['name' => 'tag1'],
                    ['name' => 'tag2']
                ]
            ]
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertNotEmpty($result);
        $this->assertCount(2, $result['tags'], 'Result should contains information about 2 tags.');
    }
}
