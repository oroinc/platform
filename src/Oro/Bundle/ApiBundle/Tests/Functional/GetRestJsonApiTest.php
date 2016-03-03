<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;

class GetRestJsonApiTest extends ApiTestCase
{
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

    /**
     * @param string $entityClass
     *
     * @dataProvider getEntities
     */
    public function testGetListRestRequests($entityClass)
    {
        $entityAlias = $this->valueNormalizer->normalizeValue(
            $entityClass,
            DataType::ENTITY_TYPE,
            $this->getRequestType()
        );

        /**
         * @TODO: Fix AbandonedCartBundle/Acl/Voter/AbandonedCartVoter (CRM-4733)
         */
        if ($entityAlias === 'abandonedcartcampaigns') {
            $this->markTestSkipped('Should be deleted after fix of AbandonedCartVoter.');
        }

        // test get list request
        $this->client->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityAlias, 'page[size]' => 1]),
            [],
            [],
            array_replace(
                $this->generateWsseAuthHeader(),
                ['CONTENT_TYPE' => 'application/vnd.api+json']
            )
        );
        $response = $this->client->getResponse();
        $this->assertApiResponseStatusCodeEquals($response, 200, $entityAlias, 'get list');

        // test get request
        $content = $this->jsonToArray($response->getContent());
        list($id, $recordExist) = $this->getGetRequestConfig($entityClass, $content);

        $this->client->request(
            'GET',
            $this->getUrl('oro_rest_api_get', ['entity' => $entityAlias, 'id' => $id]),
            [],
            [],
            array_replace(
                $this->generateWsseAuthHeader(),
                ['CONTENT_TYPE' => 'application/vnd.api+json']
            )
        );
        $this->assertApiResponseStatusCodeEquals(
            $this->client->getResponse(),
            $recordExist ? 200 : 404,
            $entityAlias,
            'get'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getGetRequestConfig($entityClass, $content)
    {
        if (array_key_exists('data', $content) && count($content['data']) === 1) {
            return [$content['data'][0]['id'], true];
        }

        return [1, false];
    }
}
