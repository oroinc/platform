<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * @dbIsolation
 */
class UpdateRestJsonApiWithTableInheritanceTest extends ApiTestCase
{
    /**
     * FQCN of the entity being used for testing.
     */
    const ENTITY_CLASS = 'Oro\Bundle\TestFrameworkBundle\Entity\TestDepartment';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            array_replace(
                $this->generateWsseAuthHeader(),
                ['CONTENT_TYPE' => 'application/vnd.api+json']
            )
        );

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestType()
    {
        return new RequestType([RequestType::REST, RequestType::JSON_API]);
    }

    public function testCreate()
    {
        $entityAlias = $this->valueNormalizer->normalizeValue(
            self::ENTITY_CLASS,
            DataType::ENTITY_TYPE,
            $this->getRequestType()
        );

        $data = [
            'data' => [
                'type'       => $entityAlias,
                'attributes' => [
                    'title' => 'Department created by API'
                ]
            ]
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityAlias]),
            $data,
            [],
            array_replace(
                $this->generateWsseAuthHeader(),
                ['CONTENT_TYPE' => 'application/vnd.api+json']
            )
        );

        $response = $this->client->getResponse();
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
        $entityAlias = $this->valueNormalizer->normalizeValue(
            self::ENTITY_CLASS,
            DataType::ENTITY_TYPE,
            $this->getRequestType()
        );

        $data = [
            'data' => [
                'type'       => $entityAlias,
                'id'         => $id,
                'attributes' => [
                    'title' => 'Department updated by API'
                ]
            ]
        ];

        $this->client->request(
            'PATCH',
            $this->getUrl('oro_rest_api_patch', ['entity' => $entityAlias, 'id' => $id]),
            $data,
            [],
            array_replace(
                $this->generateWsseAuthHeader(),
                ['CONTENT_TYPE' => 'application/vnd.api+json']
            )
        );

        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, 200);
        self::assertResponseContentTypeEquals($response, 'application/vnd.api+json');
        $result = self::jsonToArray($response->getContent());
        self::assertEquals('Department updated by API', $result['data']['attributes']['title']);
        self::assertEquals([], $result['data']['relationships']['staff']['data']);
    }
}
