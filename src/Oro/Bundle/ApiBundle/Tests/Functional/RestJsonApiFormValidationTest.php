<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;

/**
 * @dbIsolation
 */
class RestJsonApiFormValidationTest extends RestJsonApiTestCase
{
    /**
     * @param string   $entityClass
     * @param string[] $excludedActions
     *
     * @dataProvider getEntities
     */
    public function testCreateRequests($entityClass, $excludedActions)
    {
        if (in_array(ApiActions::CREATE, $excludedActions, true)) {
            return;
        }

        $entityType = $this->getEntityType($entityClass);

        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            ['data' => ['type' => $entityType, 'attributes' => ['notExistingField' => null]]]
        );
        $this->assertApiResponseStatusCodeEquals($response, [400, 403, 405], $entityType, 'post');
    }

    /**
     * @param string   $entityClass
     * @param string[] $excludedActions
     *
     * @dataProvider getEntities
     */
    public function testUpdateRequests($entityClass, $excludedActions)
    {
        if (in_array(ApiActions::UPDATE, $excludedActions, true)) {
            return;
        }

        $entityType = $this->getEntityType($entityClass);

        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_api_patch', ['entity' => $entityType, 'id' => '1']),
            ['data' => ['type' => $entityType, 'id' => '1', 'attributes' => ['notExistingField' => null]]]
        );
        $this->assertApiResponseStatusCodeEquals($response, [400, 403, 405], $entityType, 'post');
    }
}
