<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestPlainSmokeGetAndDelete;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\CheckSkippedEntityTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\RestPlainApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group regression
 */
class GetAndDeleteTest extends RestPlainApiTestCase
{
    use CheckSkippedEntityTrait;

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
                    $this->checkDeleteRequest($entityClass, $entityType, $id);
                }
            }
        });
    }

    private function checkDeleteRequest(string $entityClass, string $entityType, mixed $id): void
    {
        $response = $this->request(
            'DELETE',
            $this->getUrl($this->getItemRouteName(), ['entity' => $entityType, 'id' => $id])
        );
        if ($response->getStatusCode() !== Response::HTTP_NO_CONTENT) {
            // process delete errors
            self::assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        } else {
            // check if entity was really deleted
            $em = $this->getEntityManager($entityClass);
            $em->clear();
            self::assertTrue(null === $em->find($entityClass, $id), 'check if entity was deleted');
        }
    }

    private function checkGetRequest(string $entityType, mixed $id, int $expectedStatus): void
    {
        $response = $this->request(
            'GET',
            $this->getUrl($this->getItemRouteName(), ['entity' => $entityType, 'id' => $id])
        );
        self::assertApiResponseStatusCodeEquals($response, $expectedStatus, $entityType, 'get');
    }

    private function getFirstEntityId(string $entityClass, array $content): mixed
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

    private function getEntityIdFieldName(string $entityClass, string $action): ?string
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
