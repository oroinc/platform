<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\RestActionMapper;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Symfony\Component\HttpFoundation\Request;

class RestActionMapperTest extends \PHPUnit\Framework\TestCase
{
    private const ITEM_ROUTE = 'item_route';
    private const LIST_ROUTE = 'list_route';
    private const SUBRESOURCE_ROUTE = 'subresource_route';
    private const RELATIONSHIP_ROUTE = 'relationship_route';

    /** @var RestActionMapper */
    private $actionMapper;

    protected function setUp(): void
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
    public function testGetActions(string $templateRouteName, array $expectedActions)
    {
        self::assertEquals($expectedActions, $this->actionMapper->getActions($templateRouteName));
    }

    public function getActionsDataProvider(): array
    {
        return [
            [
                self::ITEM_ROUTE,
                [
                    ApiAction::OPTIONS,
                    ApiAction::GET,
                    ApiAction::DELETE,
                    ApiAction::UPDATE
                ]
            ],
            [
                self::LIST_ROUTE,
                [
                    ApiAction::OPTIONS,
                    ApiAction::GET_LIST,
                    ApiAction::DELETE_LIST,
                    ApiAction::CREATE,
                    ApiAction::UPDATE_LIST
                ]
            ],
            [
                self::SUBRESOURCE_ROUTE,
                [
                    ApiAction::OPTIONS,
                    ApiAction::GET_SUBRESOURCE,
                    ApiAction::UPDATE_SUBRESOURCE,
                    ApiAction::ADD_SUBRESOURCE,
                    ApiAction::DELETE_SUBRESOURCE
                ]
            ],
            [
                self::RELATIONSHIP_ROUTE,
                [
                    ApiAction::OPTIONS,
                    ApiAction::GET_RELATIONSHIP,
                    ApiAction::UPDATE_RELATIONSHIP,
                    ApiAction::ADD_RELATIONSHIP,
                    ApiAction::DELETE_RELATIONSHIP
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
                ApiAction::OPTIONS,
                ApiAction::GET,
                ApiAction::DELETE,
                ApiAction::CREATE,
                ApiAction::UPDATE
            ],
            $this->actionMapper->getActionsForResourcesWithoutIdentifier()
        );
    }

    /**
     * @dataProvider getMethodDataProvider
     */
    public function testGetMethod(string $action, string $expectedMethod)
    {
        self::assertEquals($expectedMethod, $this->actionMapper->getMethod($action));
    }

    public function getMethodDataProvider(): array
    {
        return [
            [ApiAction::OPTIONS, Request::METHOD_OPTIONS],
            [ApiAction::GET, Request::METHOD_GET],
            [ApiAction::GET_LIST, Request::METHOD_GET],
            [ApiAction::DELETE, Request::METHOD_DELETE],
            [ApiAction::DELETE_LIST, Request::METHOD_DELETE],
            [ApiAction::UPDATE, Request::METHOD_PATCH],
            [ApiAction::UPDATE_LIST, Request::METHOD_PATCH],
            [ApiAction::CREATE, Request::METHOD_POST],
            [ApiAction::GET_SUBRESOURCE, Request::METHOD_GET],
            [ApiAction::UPDATE_SUBRESOURCE, Request::METHOD_PATCH],
            [ApiAction::ADD_SUBRESOURCE, Request::METHOD_POST],
            [ApiAction::DELETE_SUBRESOURCE, Request::METHOD_DELETE],
            [ApiAction::GET_RELATIONSHIP, Request::METHOD_GET],
            [ApiAction::UPDATE_RELATIONSHIP, Request::METHOD_PATCH],
            [ApiAction::ADD_RELATIONSHIP, Request::METHOD_POST],
            [ApiAction::DELETE_RELATIONSHIP, Request::METHOD_DELETE]
        ];
    }

    public function testGetMethodForUnknownAction()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unsupported API action "unknown".');

        $this->actionMapper->getMethod('unknown');
    }
}
