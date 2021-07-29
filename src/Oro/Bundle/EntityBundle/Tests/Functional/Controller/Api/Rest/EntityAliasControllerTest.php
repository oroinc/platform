<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EntityAliasControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
    }

    public function testGetAliases()
    {
        $this->client->jsonRequest('GET', $this->getUrl('oro_api_get_entity_aliases'));
        $this->getJsonResponseContent($this->client->getResponse(), 200);
    }
}
