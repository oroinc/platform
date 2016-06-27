<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

/**
 * @dbIsolation
 */
class UpdateRestJsonApiWithTableInheritanceTest extends RestJsonApiTestCase
{
    /**
     * FQCN of the entity being used for testing.
     */
    const ENTITY_CLASS = 'Oro\Bundle\TestFrameworkBundle\Entity\TestDepartment';

    public function testCreate()
    {
        $entityType = $this->getEntityType(self::ENTITY_CLASS);

        $data = [
            'data' => [
                'type'       => $entityType,
                'attributes' => [
                    'title' => 'Department created by API'
                ]
            ]
        ];

        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            $data
        );

        self::assertResponseStatusCodeEquals($response, 201);
        self::assertResponseContentTypeEquals($response, 'application/vnd.api+json');
        $result = self::jsonToArray($response->getContent());
        self::assertEquals('Department created by API', $result['data']['attributes']['title']);
        self::assertEquals([], $result['data']['relationships']['staff']['data']);

        return $result['data']['id'];
    }

    /**
     * @depends testCreate
     *
     * @param integer $id
     */
    public function testUpdate($id)
    {
        $entityType = $this->getEntityType(self::ENTITY_CLASS);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => $id,
                'attributes' => [
                    'title' => 'Department updated by API'
                ]
            ]
        ];

        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_api_patch', ['entity' => $entityType, 'id' => $id]),
            $data
        );

        self::assertResponseStatusCodeEquals($response, 200);
        self::assertResponseContentTypeEquals($response, 'application/vnd.api+json');
        $result = self::jsonToArray($response->getContent());
        self::assertEquals('Department updated by API', $result['data']['attributes']['title']);
        self::assertEquals([], $result['data']['relationships']['staff']['data']);
    }
}
