<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiSmokeRelationships;

use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group regression
 */
class RelationshipsTest extends RestJsonApiTestCase
{
    public function testRelationship()
    {
        $this->runForSubresources(function (string $entityClass, string $associationName, ApiSubresource $subresource) {
            $entityId = $this->findEntityId($entityClass);
            if (null === $entityId) {
                return;
            }

            $entityId = $this->getRestApiEntityId($entityClass, $entityId);
            $entityType = $this->getEntityType($entityClass);

            $resourceObjectId = $this->checkGetRelationshipRequest(
                $entityType,
                $entityId,
                $associationName,
                $subresource
            );

            $this->startTransaction();
            try {
                $this->checkUpdateRelationshipRequest(
                    $entityType,
                    $entityId,
                    $associationName,
                    $resourceObjectId,
                    $subresource
                );
            } finally {
                $this->rollbackTransaction();
            }

            if ($subresource->isCollection()) {
                $this->startTransaction();
                try {
                    $this->checkDeleteRelationshipRequest(
                        $entityType,
                        $entityId,
                        $associationName,
                        $resourceObjectId,
                        $subresource
                    );
                } finally {
                    $this->rollbackTransaction();
                }

                $this->startTransaction();
                try {
                    $this->checkAddRelationshipRequest(
                        $entityType,
                        $entityId,
                        $associationName,
                        $resourceObjectId,
                        $subresource
                    );
                } finally {
                    $this->rollbackTransaction();
                }
            }
        });
    }

    protected function checkGetRelationshipRequest(
        string $entityType,
        string $entityId,
        string $associationName,
        ApiSubresource $subresource
    ): ?array {
        $parameters = [
            'entity'      => $entityType,
            'id'          => $entityId,
            'association' => $associationName
        ];
        if ($subresource->isCollection()) {
            $parameters['page[size]'] = 1;
        }
        $response = $this->getRelationship($parameters, [], [], false);

        $resourceKey = sprintf('%s(%s)->%s', $entityType, $entityId, $associationName);
        self::assertApiResponseStatusCodeEquals(
            $response,
            [Response::HTTP_OK, Response::HTTP_NOT_FOUND, Response::HTTP_METHOD_NOT_ALLOWED],
            $resourceKey,
            'get relationship'
        );

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            return null;
        }

        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayHasKey('data', $responseContent, sprintf('"get relationship" for %s', $resourceKey));
        $data = $responseContent['data'];
        $resourceObjectId = null;
        if ($subresource->isCollection()) {
            self::assertIsArray(
                $data,
                sprintf('The "data" should be an array for "get relationship" for %s', $resourceKey)
            );
            if (!empty($data)) {
                $resourceObjectId = reset($data);
            }
        } else {
            self::assertTrue(
                null === $data || is_array($data),
                sprintf('The "data" should be NULL or an array for "get relationship" for %s', $resourceKey)
            );
            if (null !== $data) {
                $resourceObjectId = $data;
            }
        }

        return $resourceObjectId;
    }

    protected function checkUpdateRelationshipRequest(
        string $entityType,
        string $entityId,
        string $associationName,
        ?array $resourceObjectId,
        ApiSubresource $subresource
    ): void {
        if (null === $resourceObjectId) {
            return;
        }
        if (in_array('update_relationship', $subresource->getExcludedActions(), true)) {
            return;
        }

        $data = ['data' => $subresource->isCollection() ? [$resourceObjectId] : $resourceObjectId];
        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => $entityId, 'association' => $associationName],
            $data,
            [],
            false
        );

        $resourceKey = sprintf('%s(%s)->%s', $entityType, $entityId, $associationName);
        self::assertUpdateApiResponseStatusCodeEquals(
            $response,
            [Response::HTTP_NO_CONTENT, Response::HTTP_NOT_FOUND, Response::HTTP_METHOD_NOT_ALLOWED],
            $resourceKey,
            'update relationship',
            $data
        );
    }

    protected function checkAddRelationshipRequest(
        string $entityType,
        string $entityId,
        string $associationName,
        ?array $resourceObjectId,
        ApiSubresource $subresource
    ): void {
        if (null === $resourceObjectId) {
            return;
        }
        if (in_array('add_relationship', $subresource->getExcludedActions(), true)) {
            return;
        }

        $data = ['data' => [$resourceObjectId]];
        $response = $this->postRelationship(
            ['entity' => $entityType, 'id' => $entityId, 'association' => $associationName],
            $data,
            [],
            false
        );

        $resourceKey = sprintf('%s(%s)->%s', $entityType, $entityId, $associationName);
        self::assertUpdateApiResponseStatusCodeEquals(
            $response,
            [Response::HTTP_NO_CONTENT, Response::HTTP_NOT_FOUND, Response::HTTP_METHOD_NOT_ALLOWED],
            $resourceKey,
            'add relationship',
            $data
        );
    }

    protected function checkDeleteRelationshipRequest(
        string $entityType,
        string $entityId,
        string $associationName,
        ?array $resourceObjectId,
        ApiSubresource $subresource
    ): void {
        if (null === $resourceObjectId) {
            return;
        }
        if (in_array('delete_relationship', $subresource->getExcludedActions(), true)) {
            return;
        }

        $response = $this->deleteRelationship(
            ['entity' => $entityType, 'id' => $entityId, 'association' => $associationName],
            ['data' => [$resourceObjectId]],
            [],
            false
        );

        $resourceKey = sprintf('%s(%s)->%s', $entityType, $entityId, $associationName);
        self::assertApiResponseStatusCodeEquals(
            $response,
            [
                Response::HTTP_NO_CONTENT,
                Response::HTTP_BAD_REQUEST,
                Response::HTTP_NOT_FOUND,
                Response::HTTP_METHOD_NOT_ALLOWED
            ],
            $resourceKey,
            'delete relationship'
        );
    }
}
