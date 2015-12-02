<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

class GetRestPlainApiTest extends ApiTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient([], $this->generateWsseAuthHeader());
    }

    /**
     * @dataProvider getEntities
     */
    public function testGetListRestRequests($entityClass)
    {
        $entityAlias = $this->entityAliasResolver->getPluralAlias($entityClass);

        //@todo: should be deleted after voter was fixed
        if ($entityAlias === 'abandonedcartcampaigns') {
            $this->markTestSkipped('Should be deleted after abandonedcartcampaigns voter was fixed');
        }

        // test get list request
        $this->client->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityAlias, 'page[size]' => 1])
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
