<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu\Matcher\Voter;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Menu\Matcher\Voter\RoutePatternVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RoutePatternVoterTest extends \PHPUnit\Framework\TestCase
{
    public function testMatchingWithoutRequest()
    {
        $item = $this->createMock(ItemInterface::class);
        $item->expects($this->never())
            ->method('getExtra');

        $voter = new RoutePatternVoter(new RequestStack());

        $this->assertNull($voter->matchItem($item));
    }

    /**
     * @dataProvider matchingDataProvider
     */
    public function testMatching(
        ?string $route,
        array $parameters,
        array|string|null $itemRoutes,
        array $itemsRoutesParameters,
        ?bool $expected
    ) {
        $item = $this->createMock(ItemInterface::class);
        $item->expects($this->any())
            ->method('getExtra')
            ->willReturnMap([
                ['routes', [], $itemRoutes],
                ['routesParameters', [], $itemsRoutesParameters],
            ]);

        $request = new Request();
        $request->attributes->set('_route', $route);
        foreach ($parameters as $name => $value) {
            $request->attributes->set($name, $value);
        }

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $voter = new RoutePatternVoter($requestStack);

        $this->assertSame($expected, $voter->matchItem($item));
    }

    public function matchingDataProvider(): array
    {
        return [
            'no request route'                                 => [null, [], 'foo', [], null],
            'no item route'                                    => ['foo', [], null, [], null],
            'same single route'                                => ['foo', [], 'foo', [], true],
            'different single route'                           => ['foo', [], 'bar', [], null],
            'matching mutiple routes'                          => ['foo', [], ['foo', 'baz'], [], true],
            'matching mutiple routes 2'                        => ['baz', [], ['foo', 'baz'], [], true],
            'different multiple routes'                        => ['foo', [], ['bar', 'baz'], [], null],
            'same single route with different parameters'      => [
                'foo',
                ['1' => 'bar'],
                'foo',
                ['foo' => ['1' => 'baz']],
                null,
            ],
            'same single route with same parameters'           => [
                'foo',
                ['1' => 'bar'],
                'foo',
                ['foo' => ['1' => 'bar']],
                true,
            ],
            'same single route with additional parameters'     => [
                'foo',
                ['1' => 'bar'],
                'foo',
                ['foo' => ['1' => 'bar', '2' => 'baz']],
                null,
            ],
            'same single route with less parameters'           => [
                'foo',
                ['1' => 'bar', '2' => 'baz'],
                'foo',
                ['foo' => ['1' => 'bar']],
                true,
            ],
            'same single route with same type parameters'      => [
                'foo',
                ['1' => 2],
                'foo',
                ['foo' => ['1' => 2]],
                true,
            ],
            'same single route with different type parameters' => [
                'foo',
                ['1' => 2],
                'foo',
                ['foo' => ['1' => '2']],
                true,
            ],
            'match regex pattern'                              => ['foo', [], '/^foo$/', [], true],
            'not match regex pattern'                          => ['foo', [], '/bar/', [], null],
            'match wildcard'                                   => ['foo', [], 'fo*', [], true],
            'not match wildcard'                               => ['foo', [], 'ba*', [], null],
        ];
    }
}
