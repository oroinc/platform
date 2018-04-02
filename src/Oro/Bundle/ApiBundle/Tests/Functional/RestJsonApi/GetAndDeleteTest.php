<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\SkippedEntitiesProvider;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

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
        if (in_array('get_list', $excludedActions, true)) {
            return;
        }

        if (in_array($entityClass, SkippedEntitiesProvider::getForGetListAction(), true)) {
            return;
        }

        $entityType = $this->getEntityType($entityClass);

        // test "get list" request
        $response = $this->cget(['entity' => $entityType, 'page[size]' => 1]);

        $id = $this->getGetEntityId(self::jsonToArray($response->getContent()));
        if (null !== $id) {
            // test "get" request
            if (!in_array('get', $excludedActions, true)) {
                $this->checkGetRequest($entityType, $id, 200);
            }
            // test "delete" request
            if (!in_array('delete', $excludedActions, true)) {
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

        $response = $this->cget(['entity' => $entityType], ['page[size]' => 1], [], false);
        if ($response->getStatusCode() === 200) {
            $id = [];
            $content = self::jsonToArray($response->getContent());
            if (!empty($content['data'])) {
                foreach ($content['data'] as $item) {
                    $id[] = $item['id'];
                }
                $response = $this->cdelete(
                    ['entity' => $entityType],
                    ['filter[id]' => implode(',', $id)],
                    [],
                    false
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
        $response = $this->delete(['entity' => $entityType, 'id' => $id], [], [], false);
        if ($response->getStatusCode() !== 204) {
            // process delete errors
            self::assertEquals(403, $response->getStatusCode());
        } elseif (!in_array('get', $excludedActions, true)) {
            // check if entity was really deleted
            $this->checkGetRequest($entityType, $id, 404);
        }
    }

    /**
     * @param string  $entityType
     * @param mixed   $id
     * @param integer $expectedStatus
     */
    protected function checkGetRequest($entityType, $id, $expectedStatus)
    {
        $response = $this->get(['entity' => $entityType, 'id' => $id], [], [], false);
        self::assertApiResponseStatusCodeEquals($response, $expectedStatus, $entityType, 'get');
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
