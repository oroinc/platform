<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiSmokeUpdateWithFieldsFilters;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\CheckSkippedEntityTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group regression
 */
class UpdateTest extends RestJsonApiTestCase
{
    use CheckSkippedEntityTrait;

    public function testUpdateWithFieldsFilter(): void
    {
        $this->runForEntities(function (string $entityClass, array $excludedActions) {
            if (in_array(ApiAction::UPDATE, $excludedActions, true)) {
                return;
            }

            $entityType = $this->getEntityType($entityClass);
            if (
                is_a($entityClass, TestFrameworkEntityInterface::class, true)
                || str_starts_with($entityType, 'testapi')
                || $this->isSkippedEntity($entityClass, ApiAction::UPDATE)
            ) {
                return;
            }

            $entityId = $this->getEntityId($entityType);
            if (null === $entityId) {
                return;
            }

            $response = $this->patch(
                ['entity' => $entityType, 'id' => $entityId],
                [
                    'filters' => sprintf('fields[%s]=id', $entityType),
                    'data' => ['type' => $entityType, 'id' => $entityId]
                ],
                [],
                false
            );
            self::assertResponseStatusCodeNotEquals($response, Response::HTTP_INTERNAL_SERVER_ERROR);

            if ($response->getStatusCode() === Response::HTTP_OK) {
                $responseContent = self::jsonToArray($response->getContent());
                self::assertArrayNotHasKey('attributes', $responseContent['data']);
                self::assertArrayNotHasKey('relationships', $responseContent['data']);
            }
        });
    }

    private function getEntityId(string $entityType): ?string
    {
        $response = $this->cget(['entity' => $entityType], ['page[size]' => 1], [], false);
        if ($response->getStatusCode() === Response::HTTP_BAD_REQUEST) {
            $response = $this->cget(['entity' => $entityType], [], [], false);
        }
        if ($response->getStatusCode() !== Response::HTTP_OK) {
            return null;
        }

        $responseContent = self::jsonToArray($response->getContent());
        if (empty($responseContent['data'])) {
            return null;
        }

        return $responseContent['data'][0]['id'];
    }
}
