<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\Rest;

use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;

class RestRoutesTest extends \PHPUnit\Framework\TestCase
{
    private const ITEM_ROUTE         = 'item_route';
    private const LIST_ROUTE         = 'list_route';
    private const SUBRESOURCE_ROUTE  = 'subresource_route';
    private const RELATIONSHIP_ROUTE = 'relationship_route';

    /** @var RestRoutes */
    private $routes;

    protected function setUp()
    {
        $this->routes = new RestRoutes(
            self::ITEM_ROUTE,
            self::LIST_ROUTE,
            self::SUBRESOURCE_ROUTE,
            self::RELATIONSHIP_ROUTE
        );
    }

    public function testGetItemRouteName()
    {
        self::assertEquals(self::ITEM_ROUTE, $this->routes->getItemRouteName());
    }

    public function testGetListRouteName()
    {
        self::assertEquals(self::LIST_ROUTE, $this->routes->getListRouteName());
    }

    public function testGetSubresourceRouteName()
    {
        self::assertEquals(self::SUBRESOURCE_ROUTE, $this->routes->getSubresourceRouteName());
    }

    public function testGetRelationshipRouteName()
    {
        self::assertEquals(self::RELATIONSHIP_ROUTE, $this->routes->getRelationshipRouteName());
    }
}
