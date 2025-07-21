<?php

namespace Oro\Component\Routing\Tests\Unit;

use Oro\Component\Routing\RouteCollectionUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteCollectionUtilTest extends TestCase
{
    public function testCloneWithoutHidden(): void
    {
        $src = new RouteCollection();
        $src->add('route1', new Route('/route1'));
        $src->add('route2', new Route('/route2', [], [], ['hidden' => true]));
        $src->add('route3', new Route('/route3', [], [], ['hidden' => false]));

        $resource1 = $this->createMock(DirectoryResource::class);
        $resource1->expects($this->any())
            ->method('getResource')
            ->willReturn('resource1');

        $resource2 = $this->createMock(DirectoryResource::class);
        $resource2->expects($this->any())
            ->method('getResource')
            ->willReturn('resource2');

        $src->addResource($resource1);
        $src->addResource($resource2);

        $result = RouteCollectionUtil::cloneWithoutHidden($src);

        $this->assertCount(2, $result);
        $this->assertNotNull($result->get('route1'));
        $this->assertNotNull($result->get('route3'));
    }

    public function testCloneWithoutHiddenWithExistingDestination(): void
    {
        $src = new RouteCollection();
        $src->add('route1', new Route('/route1'));
        $src->add('route2', new Route('/route2', [], [], ['hidden' => true]));
        $src->add('route3', new Route('/route3', [], [], ['hidden' => false]));

        $resource1 = $this->createMock(DirectoryResource::class);
        $resource1->expects($this->any())
            ->method('getResource')
            ->willReturn('resource1');

        $resource2 = $this->createMock(DirectoryResource::class);
        $resource2->expects($this->any())
            ->method('getResource')
            ->willReturn('resource2');

        $src->addResource($resource1);
        $src->addResource($resource2);

        $dest = new RouteCollection();

        $result = RouteCollectionUtil::cloneWithoutHidden($src, $dest);

        $this->assertSame($dest, $result);

        $this->assertCount(2, $result);
        $this->assertNotNull($result->get('route1'));
        $this->assertNotNull($result->get('route3'));
    }

    public function testFilterHidden(): void
    {
        $src = [
            'route1' => new Route('/route1'),
            'route2' => new Route('/route2', [], [], ['hidden' => true]),
            'route3' => new Route('/route3', [], [], ['hidden' => false])
        ];

        $result = RouteCollectionUtil::filterHidden($src);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('route1', $result);
        $this->assertArrayHasKey('route3', $result);
    }
}
