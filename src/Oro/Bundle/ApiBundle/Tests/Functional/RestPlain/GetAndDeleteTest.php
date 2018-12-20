<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestPlain;

use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\SkippedEntitiesProvider;
use Oro\Bundle\ApiBundle\Tests\Functional\RestPlainApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group regression
 */
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
        if (in_array(ApiActions::GET_LIST, $excludedActions, true)) {
            return;
        }

        if (in_array($entityClass, SkippedEntitiesProvider::getForGetListAction(), true)) {
            return;
        }

        $entityType = $this->getEntityType($entityClass);

        // test "get list" request
        $response = $this->request(
            'GET',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType, 'limit' => 1])
        );
        self::assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get list');

        $id = $this->getFirstEntityId($entityClass, self::jsonToArray($response->getContent()));
        if (null !== $id) {
            // test "get" request
            if (!in_array(ApiActions::GET, $excludedActions, true)) {
                $this->checkGetRequest($entityType, $id, Response::HTTP_OK);
            }
            // test "delete" request
            if (!in_array(ApiActions::DELETE, $excludedActions, true)) {
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
        if (in_array(ApiActions::DELETE_LIST, $excludedActions, true)
            || in_array(ApiActions::GET_LIST, $excludedActions, true)
        ) {
            return;
        }

        if (in_array($entityClass, SkippedEntitiesProvider::getForGetListAction(), true)) {
            return;
        }

        $entityType = $this->getEntityType($entityClass);

        $response = $this->request(
            'GET',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType, 'limit' => 1])
        );
        self::assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get list');

        $content = self::jsonToArray($response->getContent());
        if (!empty($content)) {
            $idFieldName = $this->getEntityIdFieldName($entityClass, ApiActions::DELETE_LIST);
            if ($idFieldName) {
                $response = $this->request(
                    'DELETE',
                    $this->getUrl(
                        $this->getListRouteName(),
                        ['entity' => $entityType, $idFieldName => $content[0][$idFieldName]]
                    )
                );
                self::assertApiResponseStatusCodeEquals(
                    $response,
                    [Response::HTTP_NO_CONTENT, Response::HTTP_FORBIDDEN],
                    $entityType,
                    'delete_list'
                );
            }
        }
    }

    /**
     * @param string   $entityType
     * @param mixed    $id
     * @param string[] $excludedActions
     */
    private function checkDeleteRequest($entityType, $id, $excludedActions)
    {
        $response = $this->request(
            'DELETE',
            $this->getUrl($this->getItemRouteName(), ['entity' => $entityType, 'id' => $id])
        );
        if ($response->getStatusCode() !== Response::HTTP_NO_CONTENT) {
            // process delete errors
            self::assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        } elseif (!in_array(ApiActions::GET, $excludedActions, true)) {
            // check if entity was really deleted
            $this->checkGetRequest($entityType, $id, Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * @param string  $entityType
     * @param mixed   $id
     * @param integer $expectedStatus
     */
    private function checkGetRequest($entityType, $id, $expectedStatus)
    {
        $response = $this->request(
            'GET',
            $this->getUrl($this->getItemRouteName(), ['entity' => $entityType, 'id' => $id])
        );
        self::assertApiResponseStatusCodeEquals($response, $expectedStatus, $entityType, 'get');
    }

    /**
     * @param string $entityClass
     * @param array  $content
     *
     * @return mixed
     */
    private function getFirstEntityId($entityClass, $content)
    {
        if (count($content) !== 1) {
            return null;
        }

        $idFieldName = $this->getEntityIdFieldName($entityClass, ApiActions::GET_LIST);
        if (!$idFieldName) {
            return null;
        }

        return $content[0][$idFieldName];
    }

    /**
     * @param string $entityClass
     * @param string $action
     *
     * @return string|null
     */
    private function getEntityIdFieldName($entityClass, $action)
    {
        $metadata = $this->getApiMetadata($entityClass, $action, true);
        if (null === $metadata) {
            return null;
        }

        $idFieldNames = $metadata->getIdentifierFieldNames();
        if (count($idFieldNames) !== 1) {
            return null;
        }

        return reset($idFieldNames);
    }
}
