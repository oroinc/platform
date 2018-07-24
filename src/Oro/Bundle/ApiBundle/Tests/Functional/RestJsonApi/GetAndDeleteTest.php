<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\SkippedEntitiesProvider;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @group regression
 */
class GetAndDeleteTest extends RestJsonApiTestCase
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
        $response = $this->cget(['entity' => $entityType, 'page[size]' => 1]);

        $id = $this->getFirstEntityId(self::jsonToArray($response->getContent()));
        if (null !== $id) {
            // test "get" request
            if (!in_array(ApiActions::GET, $excludedActions, true)) {
                $this->checkGetRequest($entityType, $id, 200);
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
        $response = $this->cget(['entity' => $entityType], ['page[size]' => 1]);

        $content = self::jsonToArray($response->getContent());
        if (!empty($content['data'])) {
            $response = $this->cdelete(
                ['entity' => $entityType],
                ['filter' => ['id' => $content['data'][0]['id']]],
                [],
                false
            );
            self::assertApiResponseStatusCodeEquals($response, [204, 403], $entityType, 'delete_list');
        }
    }

    /**
     * @param string   $entityType
     * @param mixed    $id
     * @param string[] $excludedActions
     */
    private function checkDeleteRequest($entityType, $id, $excludedActions)
    {
        $response = $this->delete(['entity' => $entityType, 'id' => $id], [], [], false);
        if ($response->getStatusCode() !== 204) {
            // process delete errors
            self::assertEquals(403, $response->getStatusCode());
        } elseif (!in_array(ApiActions::GET, $excludedActions, true)) {
            // check if entity was really deleted
            $this->checkGetRequest($entityType, $id, 404);
        }
    }

    /**
     * @param string  $entityType
     * @param mixed   $id
     * @param integer $expectedStatus
     */
    private function checkGetRequest($entityType, $id, $expectedStatus)
    {
        $response = $this->get(['entity' => $entityType, 'id' => $id], [], [], false);
        self::assertApiResponseStatusCodeEquals($response, $expectedStatus, $entityType, 'get');
    }

    /**
     * @param array $content
     *
     * @return mixed
     */
    private function getFirstEntityId($content)
    {
        return array_key_exists('data', $content) && count($content['data']) === 1
            ? $content['data'][0]['id']
            : null;
    }
}
