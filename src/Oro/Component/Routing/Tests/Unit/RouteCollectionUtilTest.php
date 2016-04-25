<?php

namespace Oro\Component\Routing\Tests\Unit;

use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Oro\Component\Routing\RouteCollectionUtil;

class RouteCollectionUtilTest extends \PHPUnit_Framework_TestCase
{
    public function testCloneWithoutHidden()
    {
        $src = new RouteCollection();
        $src->add('route1', new Route('/route1'));
        $src->add('route2', new Route('/route2', [], [], ['hidden' => true]));
        $src->add('route3', new Route('/route3', [], [], ['hidden' => false]));
        $src->addResource(new DirectoryResource('resource1'));
        $src->addResource(new DirectoryResource('resource2'));

        $result = RouteCollectionUtil::cloneWithoutHidden($src);

        $this->assertCount(2, $result);
        $this->assertNotNull($result->get('route1'));
        $this->assertNotNull($result->get('route3'));

        $this->assertCount(2, $result->getResources());
    }

    public function testCloneWithoutHiddenWithExistingDestination()
    {
        $src = new RouteCollection();
        $src->add('route1', new Route('/route1'));
        $src->add('route2', new Route('/route2', [], [], ['hidden' => true]));
        $src->add('route3', new Route('/route3', [], [], ['hidden' => false]));
        $src->addResource(new DirectoryResource('resource1'));
        $src->addResource(new DirectoryResource('resource2'));

        $dest = new RouteCollection();

        $result = RouteCollectionUtil::cloneWithoutHidden($src, $dest);

        $this->assertSame($dest, $result);

        $this->assertCount(2, $result);
        $this->assertNotNull($result->get('route1'));
        $this->assertNotNull($result->get('route3'));

        $this->assertCount(2, $result->getResources());
    }

    public function testFilterHidden()
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
