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

        $actual = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals($this->getExpectedData(), $actual);
    }

    /**
     * @return array
     */
    protected function getExpectedData()
    {
        return array_map(
            function (array $data) {
                foreach ($data as $key => $value) {
                    if (null === $value) {
                        unset($data[$key]);
                    }
                }

                return $data;
            },
            $this->provider->getFields()
        );
    }
}
