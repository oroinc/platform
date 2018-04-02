<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestPlain;

use Oro\Bundle\ApiBundle\Request\Rest\EntityIdTransformer;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\SkippedEntitiesProvider;
use Oro\Bundle\ApiBundle\Tests\Functional\RestPlainApiTestCase;

class GetAndDeleteTest extends RestPlainApiTestCase
{
    /**
     * @param string   $entityClass
     * @param string[] $excludedActions
     *
     * @dataProvider getEntities
     */
    public function testRestRequests($entityClass, $excludedActions)
    {
        if (in_array('get_list', $excludedActions, true)) {
            return;
        }

        if (in_array($entityClass, SkippedEntitiesProvider::getForGetListAction(), true)) {
            return;
        }

        $entityType = $this->getEntityType($entityClass);

        // test "get list" request
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_list', ['entity' => $entityType, 'limit' => 1])
        );
        self::assertApiResponseStatusCodeEquals($response, 200, $entityType, 'get list');

        $id = $this->getGetEntityId($entityClass, self::jsonToArray($response->getContent()));
        if (null !== $id) {
            // test "get" request
            if (!in_array('get', $excludedActions, true)) {
                // test get request
                $this->checkGetRequest($entityType, $id, 200);
            }
            // test "delete" request
            if (!in_array('delete', $excludedActions, true)) {
                // test delete request
                $this->checkDeleteRequest($entityType, $id, $excludedActions);
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

        $entityType = $this->getEntityType($entityClass);

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_list', ['entity' => $entityType, 'limit' => 1])
        );
        if ($response->getStatusCode() === 200) {
            $id = [];
            $content = self::jsonToArray($response->getContent());
            if (!empty($content)) {
                $idField = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass)[0];
                foreach ($content as $item) {
                    $id[] = $item[$idField];
                }

                $response = $this->request(
                    'DELETE',
                    $this->getUrl(
                        'oro_rest_api_list',
                        ['entity' => $entityType, 'id' => implode(',', $id)]
                    )
                );
                // @todo: remove 400 and 403 status coded here
                self::assertApiResponseStatusCodeEquals($response, [204, 400, 403], $entityType, 'delete_list');
            }
        }
    }

    /**
     * @param string   $entityType
     * @param mixed    $id
     * @param string[] $excludedActions
     */
    protected function checkDeleteRequest($entityType, $id, $excludedActions)
    {
        $response = $this->request(
            'DELETE',
            $this->getUrl('oro_rest_api_item', ['entity' => $entityType, 'id' => $id])
        );
        if ($response->getStatusCode() == 204 && !in_array('get', $excludedActions, true)) {
            // check if entity was really deleted
            $response = $this->request(
                'GET',
                $this->getUrl('oro_rest_api_item', ['entity' => $entityType, 'id' => $id])
            );
            self::assertApiResponseStatusCodeEquals($response, 404, $entityType, 'get');
        }
    }

    /**
     * @param string  $entityType
     * @param mixed   $id
     * @param integer $expectedStatus
     */
    protected function checkGetRequest($entityType, $id, $expectedStatus)
    {
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_item', ['entity' => $entityType, 'id' => $id])
        );
        self::assertApiResponseStatusCodeEquals($response, 200, $entityType, 'get');
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
            // composite identifier
            $requirements = [];
            foreach ($idFields as $field) {
                $requirements[$field] = $content[0][$field];
            }

            return http_build_query($requirements, '', EntityIdTransformer::COMPOSITE_ID_SEPARATOR);
        }
    }
}
