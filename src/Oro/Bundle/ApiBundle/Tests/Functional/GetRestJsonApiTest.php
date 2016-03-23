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
     * @param array $excludedActions
     *
     * @dataProvider getEntities
     */
    public function testRestRequests($entityClass, $excludedActions)
    {
        $entityAlias = $this->valueNormalizer->normalizeValue(
            $entityClass,
            DataType::ENTITY_TYPE,
            $this->getRequestType()
        );

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
            if (!in_array('get', $excludedActions)) {
                // test get request
                $this->checkGetRequest($entityAlias, $id, 200);
            }
            if (!in_array('delete', $excludedActions)) {
                // test delete request
                $this->checkDeleteRequest($entityAlias, $id, $excludedActions);

            }
        }

        self::cleanUpConnections();
    }

    /**
     * @param string $entityAlias
     * @param integer $id
     * @param array $excludedActions
     */
    protected function checkDeleteRequest($entityAlias, $id, $excludedActions)
    {
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
        } elseif(!in_array('get', $excludedActions)){
            // check if entity was really deleted
            $this->checkGetRequest($entityAlias, $id, 404);
        }
    }

    /**
     * @param string $entityAlias
     * @param integer $id
     * @param integer $expectedStatus
     */
    protected function checkGetRequest($entityAlias, $id, $expectedStatus)
    {
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
        $this->assertApiResponseStatusCodeEquals($this->client->getResponse(), $expectedStatus, $entityAlias, 'get');
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
