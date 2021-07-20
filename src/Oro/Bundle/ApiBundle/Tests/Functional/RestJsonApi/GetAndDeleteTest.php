<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\SkippedEntityProviderInterface;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group regression
 */
class GetAndDeleteTest extends RestJsonApiTestCase
{
    private function isSkippedEntity(string $entityClass, string $action): bool
    {
        /** @var SkippedEntityProviderInterface $provider */
        $provider = self::getContainer()->get('oro_api.tests.skipped_entity_provider');

        return $provider->isSkippedEntity($entityClass, $action);
    }

    public function testRestRequests()
    {
        $this->runForEntities(function (string $entityClass, array $excludedActions) {
            if (in_array(ApiAction::GET_LIST, $excludedActions, true)) {
                return;
            }

            if ($this->isSkippedEntity($entityClass, ApiAction::GET_LIST)) {
                return;
            }

            $entityType = $this->getEntityType($entityClass);

            // test "get list" request
            $response = $this->cget(['entity' => $entityType], ['page[size]' => 1], [], false);
            if ($response->getStatusCode() === 400) {
                $response = $this->cget(['entity' => $entityType], [], [], false);
            }
            self::assertApiResponseStatusCodeEquals($response, 200, $entityType, ApiAction::GET_LIST);
            self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);

            $id = $this->getFirstEntityId(self::jsonToArray($response->getContent()));
            if (null !== $id) {
                // test "get" request
                if (!in_array(ApiAction::GET, $excludedActions, true)) {
                    $this->checkGetRequest($entityType, $id, Response::HTTP_OK);
                }
                // test "delete" request
                if (!in_array(ApiAction::DELETE, $excludedActions, true)) {
                    $this->checkDeleteRequest($entityType, $id, $excludedActions);
                }
            }
        });
    }

    public function testDeleteList()
    {
        $this->runForEntities(function (string $entityClass, array $excludedActions) {
            if (in_array(ApiAction::DELETE_LIST, $excludedActions, true)
                || in_array(ApiAction::GET_LIST, $excludedActions, true)
            ) {
                return;
            }

            if ($this->isSkippedEntity($entityClass, ApiAction::DELETE_LIST)
                || $this->isSkippedEntity($entityClass, ApiAction::GET_LIST)
            ) {
                return;
            }

            $entityType = $this->getEntityType($entityClass);
            $response = $this->cget(['entity' => $entityType], ['page[size]' => 1], [], false);
            if ($response->getStatusCode() === 400) {
                $response = $this->cget(['entity' => $entityType], [], [], false);
            }
            self::assertApiResponseStatusCodeEquals($response, 200, $entityType, ApiAction::GET_LIST);
            self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);

            $content = self::jsonToArray($response->getContent());
            if (!empty($content['data'])) {
                $response = $this->cdelete(
                    ['entity' => $entityType],
                    ['filter' => ['id' => $content['data'][0]['id']]],
                    [],
                    false
                );
                self::assertApiResponseStatusCodeEquals(
                    $response,
                    [Response::HTTP_NO_CONTENT, Response::HTTP_FORBIDDEN],
                    $entityType,
                    'delete_list'
                );
            }
        });
    }

    /**
     * @param string   $entityType
     * @param mixed    $id
     * @param string[] $excludedActions
     */
    private function checkDeleteRequest($entityType, $id, $excludedActions)
    {
        $response = $this->delete(['entity' => $entityType, 'id' => $id], [], [], false);
        if ($response->getStatusCode() !== Response::HTTP_NO_CONTENT) {
            // process delete errors
            self::assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        } elseif (!in_array(ApiAction::GET, $excludedActions, true)) {
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
