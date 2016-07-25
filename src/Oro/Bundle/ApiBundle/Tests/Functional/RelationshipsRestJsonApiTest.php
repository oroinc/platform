<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Request\ApiSubresource;

class RelationshipsRestJsonApiTest extends RestJsonApiTestCase
{
    /**
     * @param string         $entityClass
     * @param string         $associationName
     * @param ApiSubresource $subresource
     *
     * @dataProvider getSubresources
     */
    public function testRelationship($entityClass, $associationName, ApiSubresource $subresource)
    {
        $entityId = $this->findEntityId($entityClass);
        if (null === $entityId) {
            return;
        }

        $entityId = $this->getRestApiEntityId($entityId);
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
    }

    /**
     * @param string         $entityType
     * @param string         $entityId
     * @param string         $associationName
     * @param ApiSubresource $subresource
     *
     * @return array|null
     */
    protected function checkGetRelationshipRequest(
        $entityType,
        $entityId,
        $associationName,
        ApiSubresource $subresource
    ) {
        $parameters = [
            'entity'      => $entityType,
            'id'          => $entityId,
            'association' => $associationName
        ];
        if ($subresource->isCollection()) {
            $parameters['page[size]'] = 1;
        }
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_get_relationship', $parameters)
        );

        $resourceKey = sprintf('%s(%s)->%s', $entityType, $entityId, $associationName);
        $this->assertApiResponseStatusCodeEquals($response, 200, $resourceKey, 'get relationship');

        $responseContent = $this->jsonToArray($response->getContent());
        $this->assertArrayHasKey('data', $responseContent, sprintf('"get relationship" for %s', $resourceKey));
        $data = $responseContent['data'];
        $resourceObjectId = null;
        if ($subresource->isCollection()) {
            $this->assertTrue(
                is_array($data),
                sprintf('The "data" should be an array for "get relationship" for %s', $resourceKey)
            );
            if (!empty($data)) {
                $resourceObjectId = reset($data);
            }
        } else {
            $this->assertTrue(
                null === $data || is_array($data),
                sprintf('The "data" should be NULL or an array for "get relationship" for %s', $resourceKey)
            );
            if (null !== $data) {
                $resourceObjectId = $data;
            }
        }

        return $resourceObjectId;
    }

    /**
     * @param string         $entityType
     * @param string         $entityId
     * @param string         $associationName
     * @param array|null     $resourceObjectId
     * @param ApiSubresource $subresource
     */
    protected function checkUpdateRelationshipRequest(
        $entityType,
        $entityId,
        $associationName,
        $resourceObjectId,
        ApiSubresource $subresource
    ) {
        if (null === $resourceObjectId) {
            return;
        }
        if (in_array('update_relationship', $subresource->getExcludedActions(), true)) {
            return;
        }

        $parameters = [
            'entity'      => $entityType,
            'id'          => $entityId,
            'association' => $associationName
        ];
        $data = $subresource->isCollection()
            ? [$resourceObjectId]
            : $resourceObjectId;
        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_api_patch_relationship', $parameters),
            ['data' => $data]
        );

        $resourceKey = sprintf('%s(%s)->%s', $entityType, $entityId, $associationName);
        $this->assertUpdateApiResponseStatusCodeEquals(
            $response,
            204,
            $resourceKey,
            'update relationship',
            ['data' => $data]
        );
    }

    /**
     * @param string         $entityType
     * @param string         $entityId
     * @param string         $associationName
     * @param array|null     $resourceObjectId
     * @param ApiSubresource $subresource
     */
    protected function checkAddRelationshipRequest(
        $entityType,
        $entityId,
        $associationName,
        $resourceObjectId,
        ApiSubresource $subresource
    ) {
        if (null === $resourceObjectId) {
            return;
        }
        if (in_array('add_relationship', $subresource->getExcludedActions(), true)) {
            return;
        }

        $parameters = [
            'entity'      => $entityType,
            'id'          => $entityId,
            'association' => $associationName
        ];
        $data = [$resourceObjectId];
        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post_relationship', $parameters),
            ['data' => $data]
        );

        $resourceKey = sprintf('%s(%s)->%s', $entityType, $entityId, $associationName);
        $this->assertUpdateApiResponseStatusCodeEquals(
            $response,
            204,
            $resourceKey,
            'add relationship',
            ['data' => $data]
        );
    }

    /**
     * @param string         $entityType
     * @param string         $entityId
     * @param string         $associationName
     * @param array|null     $resourceObjectId
     * @param ApiSubresource $subresource
     */
    protected function checkDeleteRelationshipRequest(
        $entityType,
        $entityId,
        $associationName,
        $resourceObjectId,
        ApiSubresource $subresource
    ) {
        if (null === $resourceObjectId) {
            return;
        }
        if (in_array('delete_relationship', $subresource->getExcludedActions(), true)) {
            return;
        }

        $parameters = [
            'entity'      => $entityType,
            'id'          => $entityId,
            'association' => $associationName
        ];
        $data = [$resourceObjectId];
        $response = $this->request(
            'DELETE',
            $this->getUrl('oro_rest_api_delete_relationship', $parameters),
            ['data' => $data]
        );

        $resourceKey = sprintf('%s(%s)->%s', $entityType, $entityId, $associationName);
        $this->assertApiResponseStatusCodeEquals($response, 204, $resourceKey, 'delete relationship');
    }
}
