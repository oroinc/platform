<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * @dbIsolation
 */
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

        $id = $this->getGetEntityId($this->jsonToArray($response->getContent()));
        if (null !== $id) {
            // test get request
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
            $this->assertApiResponseStatusCodeEquals($this->client->getResponse(), 200, $entityAlias, 'get');

            // test delete request
            $this->client->request(
                'DELETE',
                $this->getUrl('oro_rest_api_delete', ['entity' => $entityAlias, 'id' => $id]),
                [],
                [],
                array_replace(
                    $this->generateWsseAuthHeader(),
                    ['CONTENT_TYPE' => 'application/vnd.api+json']
                )
            );
            $response = $this->client->getResponse();
            if ($response->getStatusCode() !== 204) {
                // process delete errors
                $data = $this->jsonToArray($response->getContent());
                $errors = [
                    'An operation is forbidden. Reason: has assignments',
                    'An operation is forbidden. Reason: self delete',
                    'An operation is forbidden. Reason: organization has assignments'
                ];
                $this->assertContains($data['errors'][0]['detail'], $errors);
                $this->assertEquals(403, $response->getStatusCode());
            } else {
                // check if entity was really deleted
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
                $this->assertApiResponseStatusCodeEquals($this->client->getResponse(), 404, $entityAlias, 'get');
            }
        }
    }

    /**
     * @param array $content
     *
     * @return mixed
     */
    protected function getGetEntityId($content)
    {
        return array_key_exists('data', $content) && count($content['data']) === 1
            ? $content['data'][0]['id']
            : null;
    }
}
