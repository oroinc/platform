<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class EntityAliasControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
    }

    public function testGetAliases()
    {
        $this->client->request('GET', $this->getUrl('oro_api_get_entity_aliases'));
        $this->getJsonResponseContent($this->client->getResponse(), 200);
    }
}
