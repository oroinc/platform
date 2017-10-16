<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EntityControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
    }

    public function testCgetStructureAction()
    {
        $this->client->request('GET', $this->getUrl('oro_api_get_entities_structure'));
        $this->getJsonResponseContent($this->client->getResponse(), 200);
    }
}
