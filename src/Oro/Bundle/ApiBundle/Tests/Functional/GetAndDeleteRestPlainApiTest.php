<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * @dbIsolation
 */
class GetAndDeleteRestPlainApiTest extends ApiTestCase
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
        return new RequestType([RequestType::REST]);
    }

    /**
     * @param string   $entityClass
     * @param string[] $excludedActions
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

        // test "get list" request
        $this->client->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityAlias, 'limit' => 1])
        );
        $response = $this->client->getResponse();
        $this->assertApiResponseStatusCodeEquals($response, 200, $entityAlias, 'get list');

        $id = $this->getGetEntityId($entityClass, $this->jsonToArray($response->getContent()));
        if (null !== $id) {
            // test "get" request
            if (!in_array('get', $excludedActions, true)) {
                // test get request
                $this->checkGetRequest($entityAlias, $id, 200);
            }
            // test "delete" request
            if (!in_array('delete', $excludedActions, true)) {
                // test delete request
                $this->checkDeleteRequest($entityAlias, $id, $excludedActions);
            }
        }
    }

    /**
     * @param string   $entityClass
     * @param string[] $excludedActions
     *
     * @dataProvider getEntities
     */
    public function testDeleteList($entityClass, $excludedActions)
    {
        if (in_array('delete_list', $excludedActions, true)) {
            return;
        }

        $entityAlias = $this->valueNormalizer->normalizeValue(
            $entityClass,
            DataType::ENTITY_TYPE,
            $this->getRequestType()
        );
        $this->client->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityAlias, 'limit' => 1])
        );
        $response = $this->client->getResponse();
        if ($response->getStatusCode() === 200) {
            $id = [];
            $content = $this->jsonToArray($response->getContent());
            if (count($content)) {
                $idField = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass)[0];
                foreach ($content as $item) {
                    $id[] = $item[$idField];
                }
                $this->client->request(
                    'DELETE',
                    $this->getUrl(
                        'oro_rest_api_cdelete',
                        ['entity' => $entityAlias, 'id' => implode(',', $id)]
                    )
                );
                $response = $this->client->getResponse();
                if ($response->getStatusCode() == 204 && !in_array('get', $excludedActions, true)) {
                    // check if entity was really deleted
                    $this->client->request(
                        'GET',
                        $this->getUrl('oro_rest_api_get', ['entity' => $entityAlias, 'id' => $id[0]])
                    );
                    $this->assertApiResponseStatusCodeEquals($this->client->getResponse(), 404, $entityAlias, 'get');
                }
            }
        }
    }

    /**
     * @param string   $entityAlias
     * @param mixed    $id
     * @param string[] $excludedActions
     */
    protected function checkDeleteRequest($entityAlias, $id, $excludedActions)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_rest_api_delete', ['entity' => $entityAlias, 'id' => $id])
        );
        $response = $this->client->getResponse();
        if ($response->getStatusCode() == 204 && !in_array('get', $excludedActions, true)) {
            // check if entity was really deleted
            $this->client->request(
                'GET',
                $this->getUrl('oro_rest_api_get', ['entity' => $entityAlias, 'id' => $id])
            );
            $this->assertApiResponseStatusCodeEquals($this->client->getResponse(), 404, $entityAlias, 'get');
        }
    }

    /**
     * @param string  $entityAlias
     * @param mixed   $id
     * @param integer $expectedStatus
     */
    protected function checkGetRequest($entityAlias, $id, $expectedStatus)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_rest_api_get', ['entity' => $entityAlias, 'id' => $id])
        );
        $this->assertApiResponseStatusCodeEquals($this->client->getResponse(), 200, $entityAlias, 'get');
    }

    /**
     * @param string $entityClass
     * @param array  $content
     *
     * @return mixed
     */
    protected function getGetEntityId($entityClass, $content)
    {
        if (count($content) !== 1) {
            return null;
        }

        $idFields = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
        if (count($idFields) === 1) {
            // single identifier
            return $content[0][reset($idFields)];
        } else {
            // combined identifier
            $requirements = [];
            foreach ($idFields as $field) {
                $requirements[$field] = $content[0][$field];
            }

            return implode(',', $requirements);
        }
    }
}
