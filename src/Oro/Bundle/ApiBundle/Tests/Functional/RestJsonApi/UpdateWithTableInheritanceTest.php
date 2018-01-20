<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class UpdateWithTableInheritanceTest extends RestJsonApiTestCase
{
    public function testCreate()
    {
        $entityType = $this->getEntityType(TestDepartment::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'attributes' => [
                    'title' => 'Department created by API'
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

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
        $entityType = $this->getEntityType(TestDepartment::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => $id,
                'attributes' => [
                    'title' => 'Department updated by API'
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => $id], $data);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals('Department updated by API', $result['data']['attributes']['title']);
        self::assertEquals([], $result['data']['relationships']['staff']['data']);
    }
}
