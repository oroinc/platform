<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class NotAccessibleResourceTest extends RestJsonApiTestCase
{
    /**
     * @param string $action
     * @param string $method
     * @param string $route
     * @param array  $routeParameters
     *
     * @dataProvider getActions
     */
    public function testRestRequests($action, $method, $route, array $routeParameters = [])
    {
        $entityType = $this->getEntityType(EntityIdentifier::class);

        $response = $this->request(
            $method,
            $this->getUrl($route, array_merge($routeParameters, ['entity' => $entityType]))
        );
        self::assertApiResponseStatusCodeEquals($response, 404, $entityType, $action);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'resource not accessible exception',
                        'detail' => 'The resource is not accessible.'
                    ]
                ]
            ],
            json_decode($response->getContent(), true)
        );
    }

    /**
     * @return array
     */
    public function getActions()
    {
        return [
            [ApiActions::GET, 'GET', 'oro_rest_api_get', ['id' => 123]],
            [ApiActions::GET_LIST, 'GET', 'oro_rest_api_cget'],
            [ApiActions::UPDATE, 'PATCH', 'oro_rest_api_patch', ['id' => 123]],
            [ApiActions::CREATE, 'POST', 'oro_rest_api_post'],
            [ApiActions::DELETE, 'DELETE', 'oro_rest_api_delete', ['id' => 123]],
            [ApiActions::DELETE_LIST, 'DELETE', 'oro_rest_api_cdelete'],
            [
                ApiActions::GET_SUBRESOURCE,
                'GET',
                'oro_rest_api_get_subresource',
                ['id' => 123, 'association' => 'test']
            ],
            [
                ApiActions::GET_RELATIONSHIP,
                'GET',
                'oro_rest_api_get_relationship',
                ['id' => 123, 'association' => 'test']
            ],
            [
                ApiActions::UPDATE_RELATIONSHIP,
                'PATCH',
                'oro_rest_api_patch_relationship',
                ['id' => 123, 'association' => 'test']
            ],
            [
                ApiActions::ADD_RELATIONSHIP,
                'POST',
                'oro_rest_api_post_relationship',
                ['id' => 123, 'association' => 'test']
            ],
            [
                ApiActions::DELETE_RELATIONSHIP,
                'DELETE',
                'oro_rest_api_delete_relationship',
                ['id' => 123, 'association' => 'test']
            ],
        ];
    }
}
