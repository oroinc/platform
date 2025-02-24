<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EntityAliasControllerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateApiAuthHeader());
    }

    public function testGetAliases()
    {
        $this->client->jsonRequest('GET', $this->getUrl('oro_api_get_entity_aliases'));
        $this->getJsonResponseContent($this->client->getResponse(), 200);
    }
}
