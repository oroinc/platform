<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCustomization;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class CustomEntityWithDeletedFieldsTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroApiBundle/Tests/Functional/DataFixtures/custom_entity_with_deleted_fields.yml']);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'testapientity3', 'id' => '<toString(@entity->id)>']
        );

        $expectedContent = $this->getResponseData([
            'data' => [
                'type'       => 'testapientity3',
                'id'         => '<toString(@entity->id)>',
                'attributes' => ['name' => 'Test Entity']
            ]
        ]);
        self::assertEquals($expectedContent, self::jsonToArray($response->getContent()));
    }

    public function testUpdate()
    {
        $response = $this->patch(
            ['entity' => 'testapientity3', 'id' => '<toString(@entity->id)>'],
            [
                'data' => [
                    'type'       => 'testapientity3',
                    'id'         => '<toString(@entity->id)>',
                    'attributes' => ['name' => 'Updated Test Entity']
                ]
            ]
        );

        $expectedContent = $this->getResponseData([
            'data' => [
                'type'       => 'testapientity3',
                'id'         => '<toString(@entity->id)>',
                'attributes' => ['name' => 'Updated Test Entity']
            ]
        ]);
        self::assertEquals($expectedContent, self::jsonToArray($response->getContent()));
    }
}
