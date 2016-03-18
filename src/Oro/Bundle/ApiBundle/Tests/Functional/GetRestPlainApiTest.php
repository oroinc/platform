<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\RestRequest;

/**
 * @dbIsolation
 */
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
        return new RequestType([RequestType::REST]);
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
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityAlias, 'limit' => 1])
        );
        $response = $this->client->getResponse();
        $this->assertApiResponseStatusCodeEquals($response, 200, $entityAlias, 'get list');

        $id = $this->getGetEntityId($entityClass, $this->jsonToArray($response->getContent()));
        if (null !== $id) {
            // test get request
            $this->client->request(
                'GET',
                $this->getUrl('oro_rest_api_get', ['entity' => $entityAlias, 'id' => $id])
            );
            $this->assertApiResponseStatusCodeEquals($this->client->getResponse(), 200, $entityAlias, 'get');

            // test delete request
            $this->client->request(
                'DELETE',
                $this->getUrl('oro_rest_api_delete', ['entity' => $entityAlias, 'id' => $id])
            );
            $response = $this->client->getResponse();
            if ($response->getStatusCode() == 204) {
                // check if entity was really deleted
                $this->client->request(
                    'GET',
                    $this->getUrl('oro_rest_api_get', ['entity' => $entityAlias, 'id' => $id])
                );
                $this->assertApiResponseStatusCodeEquals($this->client->getResponse(), 404, $entityAlias, 'get');
            }
        }

        self::cleanUpConnections();
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

            return implode(RestRequest::ARRAY_DELIMITER, $requirements);
        }
    }
}
