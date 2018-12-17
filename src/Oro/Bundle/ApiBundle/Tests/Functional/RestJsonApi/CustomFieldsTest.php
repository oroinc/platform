<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOwner;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class CustomFieldsTest extends RestJsonApiTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/custom_fields.yml'
        ]);
    }

    /**
     * @param Response $response
     * @param int      $entityId
     */
    private function assertHasCustomField(Response $response, $entityId)
    {
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapiowners',
                    'id'         => (string)$entityId,
                    'attributes' => [
                        'name'               => 'Owner 1',
                        'extend_description' => 'Description for Owner 1'
                    ]
                ]
            ],
            $response
        );
    }

    /**
     * @param Response $response
     * @param int      $entityId
     */
    private function assertNotHasCustomField(Response $response, $entityId)
    {
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapiowners',
                    'id'         => (string)$entityId,
                    'attributes' => [
                        'name' => 'Owner 1'
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey(
            'extend_description',
            $responseContent['data']['attributes']
        );
    }

    public function testCustomFieldsShouldBeAddedByDefault()
    {
        $entityId = $this->getReference('owner1')->id;
        $this->getEntityManager()->clear();
        $response = $this->get(
            ['entity' => 'testapiowners', 'id' => (string)$entityId]
        );
        $this->assertHasCustomField($response, $entityId);
    }

    public function testCustomFieldsShouldNotBeAddedWhenExclusionPolicyEqualsToCustomFields()
    {
        $this->appendEntityConfig(
            TestOwner::class,
            ['exclusion_policy' => 'custom_fields']
        );
        $entityId = $this->getReference('owner1')->id;
        $this->getEntityManager()->clear();
        $response = $this->get(
            ['entity' => 'testapiowners', 'id' => (string)$entityId]
        );
        $this->assertNotHasCustomField($response, $entityId);
    }

    public function testShouldBePossibleToAddCustomFieldWhenExclusionPolicyEqualsToCustomFields()
    {
        $this->appendEntityConfig(
            TestOwner::class,
            [
                'exclusion_policy' => 'custom_fields',
                'fields'           => [
                    'extend_description' => null
                ]
            ]
        );
        $entityId = $this->getReference('owner1')->id;
        $this->getEntityManager()->clear();
        $response = $this->get(
            ['entity' => 'testapiowners', 'id' => (string)$entityId]
        );
        $this->assertHasCustomField($response, $entityId);
    }

    public function testShouldBePossibleToAddAndRenameCustomFieldWhenExclusionPolicyEqualsToCustomFields()
    {
        $this->appendEntityConfig(
            TestOwner::class,
            [
                'exclusion_policy' => 'custom_fields',
                'fields'           => [
                    'extendDescription' => [
                        'property_path' => 'extend_description'
                    ]
                ]
            ]
        );
        $entityId = $this->getReference('owner1')->id;
        $this->getEntityManager()->clear();
        $response = $this->get(
            ['entity' => 'testapiowners', 'id' => (string)$entityId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapiowners',
                    'id'         => (string)$entityId,
                    'attributes' => [
                        'name'              => 'Owner 1',
                        'extendDescription' => 'Description for Owner 1'
                    ]
                ]
            ],
            $response
        );
    }

    public function testCustomFieldsShouldNotBeAddedWhenExclusionPolicyEqualsToAll()
    {
        $this->appendEntityConfig(
            TestOwner::class,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'   => null,
                    'name' => null
                ]
            ]
        );
        $entityId = $this->getReference('owner1')->id;
        $this->getEntityManager()->clear();
        $response = $this->get(
            ['entity' => 'testapiowners', 'id' => (string)$entityId]
        );
        $this->assertNotHasCustomField($response, $entityId);
    }
}
