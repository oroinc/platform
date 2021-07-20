<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestPlain;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\SkippedEntityProviderInterface;
use Oro\Bundle\ApiBundle\Tests\Functional\RestPlainApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group regression
 */
class GetAndDeleteTest extends RestPlainApiTestCase
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
            $response = $this->request(
                'GET',
                $this->getUrl($this->getListRouteName(), ['entity' => $entityType, 'limit' => 1])
            );
            self::assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get list');

            $id = $this->getFirstEntityId($entityClass, self::jsonToArray($response->getContent()));
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

            $response = $this->request(
                'GET',
                $this->getUrl($this->getListRouteName(), ['entity' => $entityType, 'limit' => 1])
            );
            self::assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get list');

            $content = self::jsonToArray($response->getContent());
            if (!empty($content)) {
                $idFieldName = $this->getEntityIdFieldName($entityClass, ApiAction::DELETE_LIST);
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
        });
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

        $idFieldName = $this->getEntityIdFieldName($entityClass, ApiAction::GET_LIST);
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
