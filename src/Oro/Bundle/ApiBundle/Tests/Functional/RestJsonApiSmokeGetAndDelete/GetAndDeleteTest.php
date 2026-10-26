<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiSmokeGetAndDelete;

use Oro\Bundle\ApiBundle\Model\EnumOption as ApiEnumOption;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\CheckSkippedEntityTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption as EntityEnumOption;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group regression
 */
class GetAndDeleteTest extends RestJsonApiTestCase
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
                    $this->checkDeleteRequest($entityClass, $entityType, $id);
                }
            }
        });
    }

    private function checkDeleteRequest(string $entityClass, string $entityType, mixed $id): void
    {
        $response = $this->delete(['entity' => $entityType, 'id' => $id], [], [], false);
        if ($response->getStatusCode() !== Response::HTTP_NO_CONTENT) {
            // process delete errors
            if (ApiEnumOption::class === $entityClass && Response::HTTP_CONFLICT === $response->getStatusCode()) {
                // An immutable option or an option of a system enum cannot be deleted.
                return;
            }

            self::assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        } else {
            // check if entity was really deleted
            if (ApiEnumOption::class === $entityClass) {
                $entityClass = EntityEnumOption::class;
            }
            $em = $this->getEntityManager($entityClass);
            $em->clear();
            self::assertTrue(null === $em->find($entityClass, $id), 'check if entity was deleted');
        }
    }

    private function checkGetRequest(string $entityType, mixed $id, int $expectedStatus): void
    {
        $response = $this->get(['entity' => $entityType, 'id' => $id], [], [], false);
        self::assertApiResponseStatusCodeEquals($response, $expectedStatus, $entityType, 'get');
    }

    private function getFirstEntityId(array $content): mixed
    {
        return array_key_exists('data', $content) && count($content['data']) === 1
            ? $content['data'][0]['id']
            : null;
    }
}
