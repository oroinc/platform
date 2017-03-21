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
        $this->provider = $this->client->getContainer()->get('oro_workflow.entity_field_list_provider');
    }

    public function testGetAction()
    {
        $this->client->request('GET', $this->getUrl('oro_api_workflow_entity_get'));

        $actual = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($actual);
        $this->assertEquals($this->getExpectedData(), $actual);
        foreach ($actual as $entityData) {
            $this->assertArrayHasKey('name', $entityData);
            $this->assertNotEmpty($entityData['fields']);
            foreach ($entityData['fields'] as $fieldData) {
                if (array_key_exists('relation_type', $fieldData)) {
                    $this->assertArrayHasKey('name', $fieldData);
                    $this->assertNotRegExp(
                        '/many$/',
                        $fieldData['relation_type'],
                        sprintf('Unsupported *toMany relation present %s:%s', $entityData['name'], $fieldData['name'])
                    );

                    $this->assertArrayHasKey('type', $fieldData);
                    $this->assertNotEquals('multiEnum', $fieldData['type']);
                }
            }
        }
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
            $this->provider->getFields(false, false, true, false, true, true)
        );
    }
}
