<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group regression
 */
class GetSubresourcesTest extends RestJsonApiTestCase
{
    /**
     * @param string         $entityClass
     * @param string         $associationName
     * @param ApiSubresource $subresource
     *
     * @dataProvider getSubresources
     */
    public function testGetSubresource($entityClass, $associationName, ApiSubresource $subresource)
    {
        $entityId = $this->findEntityId($entityClass);
        if (null === $entityId) {
            return;
        }

        $entityId = $this->getRestApiEntityId($entityClass, $entityId);
        $entityType = $this->getEntityType($entityClass);

        $parameters = [
            'entity'      => $entityType,
            'id'          => $entityId,
            'association' => $associationName,
            'page[size]'  => 1
        ];
        if ($subresource->isCollection()) {
            $parameters['page[size]'] = 1;
        }
        $response = $this->getSubresource($parameters, [], [], false);
        self::assertApiResponseStatusCodeEquals(
            $response,
            [Response::HTTP_OK, Response::HTTP_NOT_FOUND],
            sprintf('%s(%s)->%s', $entityType, $entityId, $associationName),
            'get subresource'
        );
    }
}
