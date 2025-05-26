<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\Rest;

use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use PHPUnit\Framework\TestCase;

class RestRoutesTest extends TestCase
{
    private const string ITEM_ROUTE = 'item_route';
    private const string LIST_ROUTE = 'list_route';
    private const string SUBRESOURCE_ROUTE = 'subresource_route';
    private const string RELATIONSHIP_ROUTE = 'relationship_route';

    private RestRoutes $routes;

    #[\Override]
    protected function setUp(): void
    {
        $this->routes = new RestRoutes(
            self::ITEM_ROUTE,
            self::LIST_ROUTE,
            self::SUBRESOURCE_ROUTE,
            self::RELATIONSHIP_ROUTE
        );
    }

    public function testGetItemRouteName(): void
    {
        self::assertEquals(self::ITEM_ROUTE, $this->routes->getItemRouteName());
    }

    public function testGetListRouteName(): void
    {
        self::assertEquals(self::LIST_ROUTE, $this->routes->getListRouteName());
    }

    public function testGetSubresourceRouteName(): void
    {
        self::assertEquals(self::SUBRESOURCE_ROUTE, $this->routes->getSubresourceRouteName());
    }

    public function testGetRelationshipRouteName(): void
    {
        self::assertEquals(self::RELATIONSHIP_ROUTE, $this->routes->getRelationshipRouteName());
    }
}
