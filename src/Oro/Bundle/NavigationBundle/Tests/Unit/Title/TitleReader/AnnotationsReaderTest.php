<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Title\TitleReader;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\Cache;
use Oro\Bundle\NavigationBundle\Annotation\TitleTemplate;
use Oro\Bundle\NavigationBundle\Title\TitleReader\AnnotationsReader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Router;

class AnnotationsReaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var Reader|\PHPUnit\Framework\MockObject\MockObject */
    private $reader;

    /** @var Router|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var AnnotationsReader */
    private $annotationReader;


    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->reader = $this->createMock(Reader::class);
        $this->router = $this->createMock(Router::class);
        $this->cache = $this->createMock(Cache::class);

        $this->annotationReader = new AnnotationsReader($this->reader, $this->router, $this->cache);
    }

    public function testGetTitleWithoutCache()
    {
        $routeName = 'test_route';

        $annotation = $this->getMockBuilder(TitleTemplate::class)->disableOriginalConstructor()->getMock();
        $annotation->expects($this->once())
            ->method('getValue')
            ->willReturn('Title Template');

        $classes = [$routeName => AnnotationsReaderTest::class . '::testGetTitleWithoutCache'];

        $this->cache
            ->expects($this->once())
            ->method('fetch')
            ->with(AnnotationsReader::CACHE_KEY)
            ->willReturn(false);

        $this->cache
            ->expects($this->once())
            ->method('save')
            ->with(AnnotationsReader::CACHE_KEY, $classes);

        $route = new Route('/');
        $route->setDefault('_controller', AnnotationsReaderTest::class . '::testGetTitleWithoutCache');

        $this->router
            ->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn([$routeName => $route]);

        $reflectionMethod = new \ReflectionMethod(AnnotationsReaderTest::class, 'testGetTitleWithoutCache');

        $this->reader
            ->expects($this->once())
            ->method('getMethodAnnotation')
            ->with($reflectionMethod, TitleTemplate::class)
            ->willReturn($annotation);

        $this->assertEquals('Title Template', $this->annotationReader->getTitle($routeName));
    }

    public function testGetTitleWithCache()
    {
        $routeName = 'test_route';

        $annotation = $this->getMockBuilder(TitleTemplate::class)->disableOriginalConstructor()->getMock();
        $annotation->expects($this->once())
            ->method('getValue')
            ->willReturn('Title Template');

        $classes = [$routeName => AnnotationsReaderTest::class . '::testGetTitleWithCache'];

        $this->cache
            ->expects($this->once())
            ->method('fetch')
            ->with(AnnotationsReader::CACHE_KEY)
            ->willReturn($classes);


        $reflectionMethod = new \ReflectionMethod(AnnotationsReaderTest::class, 'testGetTitleWithCache');

        $this->reader
            ->expects($this->once())
            ->method('getMethodAnnotation')
            ->with($reflectionMethod, TitleTemplate::class)
            ->willReturn($annotation);

        $this->assertEquals('Title Template', $this->annotationReader->getTitle($routeName));
    }

    public function testGetTitleEmpty()
    {
        $routeName = 'test_route';

        $classes = [$routeName => AnnotationsReaderTest::class . '::testGetTitleEmpty'];

        $this->cache
            ->expects($this->once())
            ->method('fetch')
            ->with(AnnotationsReader::CACHE_KEY)
            ->willReturn($classes);

        $reflectionMethod = new \ReflectionMethod(AnnotationsReaderTest::class, 'testGetTitleEmpty');

        $this->reader
            ->expects($this->once())
            ->method('getMethodAnnotation')
            ->with($reflectionMethod, TitleTemplate::class)
            ->willReturn(null);

        $this->assertNull($this->annotationReader->getTitle($routeName));
    }

    public function testGetTitleControllerIsEmpty()
    {
        $routeName = 'test_route';

        $classes = [];

        $this->cache
            ->expects($this->once())
            ->method('fetch')
            ->with(AnnotationsReader::CACHE_KEY)
            ->willReturn($classes);

        $this->reader
            ->expects($this->never())
            ->method('getMethodAnnotation');

        $this->assertNull($this->annotationReader->getTitle($routeName));
    }

    public function testGetTitleControllerIsUndefined()
    {
        $routeName = 'test_route';

        $classes = [$routeName => 'WrongControllerAndMethod'];

        $this->cache
            ->expects($this->once())
            ->method('fetch')
            ->with(AnnotationsReader::CACHE_KEY)
            ->willReturn($classes);

        $this->reader
            ->expects($this->never())
            ->method('getMethodAnnotation');

        $this->assertNull($this->annotationReader->getTitle($routeName));
    }
}
