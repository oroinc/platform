<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Request\RequestType;

class GetRestPlainApiTest extends ApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestType()
    {
        return [RequestType::REST];
    }

    /**
     * @param string $entityClass
     *
     * @dataProvider getEntities
     */
    public function testGetListRestRequests($entityClass)
    {
        $entityAlias = $this->entityClassTransformer->transform($entityClass);

        /**
         * @TODO: Fix AbandonedCartBundle/Acl/Voter/AbandonedCartVoter (CRM-4733)
         */
        if ($entityAlias === 'abandonedcartcampaigns') {
            $this->markTestSkipped('Should be deleted after fix of AbandonedCartVoter.');
        }

        // test get list request
        $this->client->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityAlias, 'limit' => 1])
        );
        $response = $this->client->getResponse();
        $this->assertApiResponseStatusCodeEquals($response, 200, $entityAlias, 'get list');

        // test get request
        $content = $this->jsonToArray($response->getContent());
        list($id, $recordExist) = $this->getGetRequestConfig($entityClass, $content);

        $this->client->request(
            'GET',
            $this->getUrl('oro_rest_api_get', ['entity' => $entityAlias, 'id' => $id])
        );
        $this->assertApiResponseStatusCodeEquals(
            $this->client->getResponse(),
            $recordExist ? 200 : 404,
            $entityAlias,
            'get'
        );
    }
}
