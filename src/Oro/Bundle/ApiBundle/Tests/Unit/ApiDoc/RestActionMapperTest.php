<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\RestActionMapper;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Symfony\Component\HttpFoundation\Request;

class RestActionMapperTest extends \PHPUnit\Framework\TestCase
{
    private const ITEM_ROUTE         = 'item_route';
    private const LIST_ROUTE         = 'list_route';
    private const SUBRESOURCE_ROUTE  = 'subresource_route';
    private const RELATIONSHIP_ROUTE = 'relationship_route';

    /** @var RestActionMapper */
    private $actionMapper;

    protected function setUp()
    {
        $routes = new RestRoutes(
            self::ITEM_ROUTE,
            self::LIST_ROUTE,
            self::SUBRESOURCE_ROUTE,
            self::RELATIONSHIP_ROUTE
        );
        $this->actionMapper = new RestActionMapper($routes);
    }

    /**
     * @dataProvider getActionsDataProvider
     */
    public function testGetActions($templateRouteName, $expectedAction)
    {
        self::assertEquals($expectedAction, $this->actionMapper->getActions($templateRouteName));
    }

    public function getActionsDataProvider()
    {
        return [
            [
                self::ITEM_ROUTE,
                [
                    ApiActions::OPTIONS,
                    ApiActions::GET,
                    ApiActions::DELETE,
                    ApiActions::UPDATE
                ]
            ],
            [
                self::LIST_ROUTE,
                [
                    ApiActions::OPTIONS,
                    ApiActions::GET_LIST,
                    ApiActions::DELETE_LIST,
                    ApiActions::CREATE
                ]
            ],
            [
                self::SUBRESOURCE_ROUTE,
                [
                    ApiActions::OPTIONS,
                    ApiActions::GET_SUBRESOURCE,
                    ApiActions::UPDATE_SUBRESOURCE,
                    ApiActions::ADD_SUBRESOURCE,
                    ApiActions::DELETE_SUBRESOURCE
                ]
            ],
            [
                self::RELATIONSHIP_ROUTE,
                [
                    ApiActions::OPTIONS,
                    ApiActions::GET_RELATIONSHIP,
                    ApiActions::UPDATE_RELATIONSHIP,
                    ApiActions::ADD_RELATIONSHIP,
                    ApiActions::DELETE_RELATIONSHIP
                ]
            ]
        ];
    }

    public function testGetActionsForUnknownRoute()
    {
        self::assertSame([], $this->actionMapper->getActions('unknown'));
    }

    public function testGetActionsForResourcesWithoutIdentifier()
    {
        self::assertEquals(
            [
                ApiActions::OPTIONS,
                ApiActions::GET,
                ApiActions::DELETE,
                ApiActions::CREATE,
                ApiActions::UPDATE
            ],
            $this->actionMapper->getActionsForResourcesWithoutIdentifier()
        );
    }

    /**
     * @dataProvider getMethodDataProvider
     */
    public function testGetMethod($action, $expectedMethod)
    {
        self::assertEquals($expectedMethod, $this->actionMapper->getMethod($action));
    }

    public function getMethodDataProvider()
    {
        return [
            [ApiActions::OPTIONS, Request::METHOD_OPTIONS],
            [ApiActions::GET, Request::METHOD_GET],
            [ApiActions::GET_LIST, Request::METHOD_GET],
            [ApiActions::DELETE, Request::METHOD_DELETE],
            [ApiActions::DELETE_LIST, Request::METHOD_DELETE],
            [ApiActions::UPDATE, Request::METHOD_PATCH],
            [ApiActions::CREATE, Request::METHOD_POST],
            [ApiActions::GET_SUBRESOURCE, Request::METHOD_GET],
            [ApiActions::UPDATE_SUBRESOURCE, Request::METHOD_PATCH],
            [ApiActions::ADD_SUBRESOURCE, Request::METHOD_POST],
            [ApiActions::DELETE_SUBRESOURCE, Request::METHOD_DELETE],
            [ApiActions::GET_RELATIONSHIP, Request::METHOD_GET],
            [ApiActions::UPDATE_RELATIONSHIP, Request::METHOD_PATCH],
            [ApiActions::ADD_RELATIONSHIP, Request::METHOD_POST],
            [ApiActions::DELETE_RELATIONSHIP, Request::METHOD_DELETE]
        ];
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Unsupported API action "unknown".
     */
    public function testGetMethodForUnknownAction()
    {
        $this->actionMapper->getMethod('unknown');
    }
}
