<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Condition;

use Oro\Bundle\ActionBundle\Condition\RouteExists;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class RouteExistsTest extends \PHPUnit\Framework\TestCase
{
    const PROPERTY_PATH_NAME = 'testPropertyPath';

    /**
     * @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $router;

    /**
     * @var RouteExists
     */
    protected $routeExists;

    /**
     * @var PropertyPathInterface
     */
    protected $propertyPath;

    /**
     * @var RouteCollection|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $routeCollection;

    protected function setUp()
    {
        $this->routeCollection = $this->getMockBuilder(RouteCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->router = $this->createMock(RouterInterface::class);
        $this->router->expects($this->any())
            ->method('getRouteCollection')
            ->willReturn($this->routeCollection);

        $this->propertyPath = $this->getMockBuilder(PropertyPathInterface::class)
            ->getMock();
        $this->propertyPath->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue(self::PROPERTY_PATH_NAME));
        $this->propertyPath->expects($this->any())
            ->method('getElements')
            ->will($this->returnValue([self::PROPERTY_PATH_NAME]));

        $this->routeExists = new RouteExists($this->router);
    }

    public function testGetName()
    {
        $this->assertEquals('route_exists', $this->routeExists->getName());
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(RouteExists::class, $this->routeExists->initialize([$this->propertyPath]));
    }

    public function testToArray()
    {
        $result = $this->routeExists->initialize([$this->propertyPath])->toArray();

        $this->assertEquals('$' . self::PROPERTY_PATH_NAME, $result['@route_exists']['parameters'][0]);
    }

    public function testCompile()
    {
        $result = $this->routeExists->compile('$factoryAccessor');

        $this->assertContains('$factoryAccessor->create(\'route_exists\'', $result);
    }

    public function testSetContextAccessor()
    {
        /** @var ContextAccessorInterface|\PHPUnit\Framework\MockObject\MockObject $contextAccessor **/
        $contextAccessor = $this->getMockBuilder(ContextAccessorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->routeExists->setContextAccessor($contextAccessor);

        $reflection = new \ReflectionProperty(get_class($this->routeExists), 'contextAccessor');
        $reflection->setAccessible(true);

        $this->assertInstanceOf(get_class($contextAccessor), $reflection->getValue($this->routeExists));
    }

    /**
     * @dataProvider dataProvider
     * @param Route|null $route
     * @param bool $expected
     */
    public function testEvaluates($route, $expected)
    {
        $this->routeCollection->expects($this->any())
            ->method('get')
            ->willReturn($route);

        /** @var ContextAccessorInterface|\PHPUnit\Framework\MockObject\MockObject $contextAccessor **/
        $contextAccessor = $this->getMockBuilder(ContextAccessorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextAccessor->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue('oro_bundle_route'));

        $this->routeExists->initialize([$this->propertyPath])->setContextAccessor($contextAccessor);

        $this->assertEquals($expected, $this->routeExists->evaluate('oro_bundle_route'));
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            'route exists' => [
                'route' => new Route('/'),
                'expected' => true,
            ],
            'route not exists' => [
                'route' => null,
                'expected' => false,
            ],
        ];
    }
}
