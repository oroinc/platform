<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class MetaPropertiesTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/meta_properties.yml'
        ]);
    }

    public function testGetForEntityWithMetaProperty(): void
    {
        $response = $this->get(
            ['entity' => 'testapicustommagazines', 'id' => '<toString(@magazine1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'testapicustommagazines',
                    'id' => '<toString(@magazine1->id)>',
                    'meta' => ['issueDate' => '2025-02-20'],
                    'attributes' => ['name' => 'Magazine 1']
                ]
            ],
            $response
        );
    }

    public function testGetForEntityWithMetaPropertyWhenMetaPropertyIsNotRequested(): void
    {
        $response = $this->get(
            ['entity' => 'testapicustommagazines', 'id' => '<toString(@magazine1->id)>'],
            ['fields[testapicustommagazines]' => 'name']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'testapicustommagazines',
                    'id' => '<toString(@magazine1->id)>',
                    'attributes' => ['name' => 'Magazine 1']
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
    }

    public function testGetForEntityWithRequiredMetaProperty(): void
    {
        $response = $this->get(
            ['entity' => 'testapicustomarticles', 'id' => '<toString(@article1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'testapicustomarticles',
                    'id' => '<toString(@article1->id)>',
                    'meta' => ['length' => 14],
                    'attributes' => ['headline' => 'Article 1']
                ]
            ],
            $response
        );
    }

    public function testGetForEntityWithRequiredMetaPropertyWhenMetaPropertyIsNotRequested(): void
    {
        $response = $this->get(
            ['entity' => 'testapicustomarticles', 'id' => '<toString(@article1->id)>'],
            ['fields[testapicustomarticles]' => 'headline']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'testapicustomarticles',
                    'id' => '<toString(@article1->id)>',
                    'meta' => ['length' => 14],
                    'attributes' => ['headline' => 'Article 1']
                ]
            ],
            $response
        );
    }
}
