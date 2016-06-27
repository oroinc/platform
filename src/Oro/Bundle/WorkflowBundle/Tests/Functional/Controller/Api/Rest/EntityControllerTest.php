<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EntityControllerTest extends WebTestCase
{
    /**
     * @var EntityWithFieldsProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->provider = $this->client->getContainer()->get('oro_entity.entity_field_list_provider');
    }

    public function testGetAction()
    {
        $this->client->request('GET', $this->getUrl('oro_api_workflow_entity_get'));

        $this->assertEquals(
            array_keys($this->provider->getFields()),
            array_keys($this->getJsonResponseContent($this->client->getResponse(), 200))
        );
    }
}
