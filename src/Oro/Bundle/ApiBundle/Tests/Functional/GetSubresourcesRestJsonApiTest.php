<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Request\ApiSubresource;

class GetSubresourcesRestJsonApiTest extends RestJsonApiTestCase
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

        $entityId = $this->getRestApiEntityId($entityId);
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
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_get_subresource', $parameters)
        );
        $this->assertApiResponseStatusCodeEquals(
            $response,
            200,
            sprintf('%s(%s)->%s', $entityType, $entityId, $associationName),
            'get subresource'
        );
    }
}
