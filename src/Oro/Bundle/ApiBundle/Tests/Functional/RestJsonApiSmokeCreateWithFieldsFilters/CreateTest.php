<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiSmokeCreateWithFieldsFilters;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\CheckSkippedEntityTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group regression
 */
class CreateTest extends RestJsonApiTestCase
{
    use CheckSkippedEntityTrait;

    public function testCreateWithFieldsFilter(): void
    {
        $this->runForEntities(function (string $entityClass, array $excludedActions) {
            if (in_array(ApiAction::CREATE, $excludedActions, true)) {
                return;
            }

            $entityType = $this->getEntityType($entityClass);
            if (
                is_a($entityClass, TestFrameworkEntityInterface::class, true)
                || str_starts_with($entityType, 'testapi')
                || $this->isSkippedEntity($entityClass, ApiAction::CREATE)
            ) {
                return;
            }

            $response = $this->post(
                ['entity' => $entityType],
                ['filters' => sprintf('fields[%s]=id', $entityType), 'data' => ['type' => $entityType]],
                [],
                false
            );
            self::assertResponseStatusCodeNotEquals($response, Response::HTTP_INTERNAL_SERVER_ERROR);

            if (
                $response->getStatusCode() === Response::HTTP_OK
                || $response->getStatusCode() === Response::HTTP_CREATED
            ) {
                $responseContent = self::jsonToArray($response->getContent());
                self::assertArrayNotHasKey('attributes', $responseContent['data']);
                self::assertArrayNotHasKey('relationships', $responseContent['data']);
            }
        });
    }
}
