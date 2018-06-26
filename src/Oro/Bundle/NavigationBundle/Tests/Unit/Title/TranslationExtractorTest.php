<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Title;

use Oro\Bundle\NavigationBundle\Title\TitleReader\TitleReaderRegistry;
use Oro\Bundle\NavigationBundle\Title\TranslationExtractor;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\MessageCatalogue;

class TranslationExtractorTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationExtractor */
    private $translatorExtractor;

    /** @var TitleReaderRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $titleReaderRegistry;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->titleReaderRegistry = $this->getMockBuilder(TitleReaderRegistry::class)->getMock();
        $this->router = $this->getMockBuilder(RouterInterface::class)->getMock();

        $this->translatorExtractor = new TranslationExtractor(
            $this->titleReaderRegistry,
            $this->router
        );
    }

    public function testExtract()
    {
        $routes = ['route_1' => new Route('route_1', ['_controller' => 'TestBundle/Controller/TestController'])];

        /** @var RouteCollection|\PHPUnit\Framework\MockObject\MockObject $routeCollection */
        $routeCollection = $this->getMockBuilder(RouteCollection::class)->getMock();
        $routeCollection->expects($this->once())
            ->method('all')
            ->willReturn($routes);

        $this->router
            ->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn($routeCollection);

        $this->titleReaderRegistry
            ->expects($this->once())
            ->method('getTitleByRoute')
            ->with('route_1')
            ->willReturn('test.title');

        /** @var MessageCatalogue|\PHPUnit\Framework\MockObject\MockObject $catalogue */
        $catalogue = $this->getMockBuilder(MessageCatalogue::class)->disableOriginalConstructor()->getMock();
        $catalogue->expects($this->once())
            ->method('set')
            ->with('test.title', 'prefix_test.title');

        $this->translatorExtractor->setPrefix('prefix_');
        $this->translatorExtractor->extract('TestBundle', $catalogue);
    }
}
