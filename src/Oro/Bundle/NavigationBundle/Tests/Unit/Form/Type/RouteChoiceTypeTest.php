<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType;
use Oro\Bundle\NavigationBundle\Provider\TitleService;
use Oro\Bundle\NavigationBundle\Provider\TitleTranslator;
use Oro\Bundle\NavigationBundle\Title\TitleReader\TitleReaderRegistry;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class RouteChoiceTypeTest extends FormIntegrationTestCase
{
    /**
     * @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $router;

    /**
     * @var TitleReaderRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $readerRegistry;

    /**
     * @var TitleTranslator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    /**
     * @var TitleService|\PHPUnit\Framework\MockObject\MockObject
     */
    private $titleService;

    /**
     * @var Cache|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cache;

    /**
     * @var RouteChoiceType
     */
    private $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->readerRegistry = $this->createMock(TitleReaderRegistry::class);
        $this->translator = $this->createMock(TitleTranslator::class);
        $this->titleService = $this->createMock(TitleService::class);
        $this->cache = $this->createMock(Cache::class);

        $this->formType = new RouteChoiceType(
            $this->router,
            $this->readerRegistry,
            $this->translator,
            $this->titleService,
            $this->cache
        );

        parent::setUp();
    }

    public function testGetParent()
    {
        $this->assertEquals(Select2ChoiceType::class, $this->formType->getParent());
    }

    public function testConfigureOptionsDoNotAddTitles()
    {
        $routeCollection = $this->getRouteCollection();

        $this->router->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn($routeCollection);

        $this->readerRegistry
            ->expects($this->never())
            ->method($this->anything());

        $this->cache->expects($this->once())
            ->method('contains')
            ->willReturn(false);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(
                $this->isType('string'),
                [
                    'oro_route_get_simple',
                    'oro_route_get',
                    'oro_route_get_post',
                    'oro_route_with_option',
                    'oro_route_get_simple_no_title'
                ]
            );

        $options = ['add_titles' => false, 'menu_name' => 'menu'];

        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve($options);

        $expectedChoices = [
            'Oro Route Get Simple' => 'oro_route_get_simple',
            'Oro Route Get' => 'oro_route_get',
            'Oro Route Get Post' => 'oro_route_get_post',
            'Oro Route With Option' => 'oro_route_with_option',
            'Oro Route Get Simple No Title' => 'oro_route_get_simple_no_title',
        ];

        $this->assertArrayHasKey('choices', $resolvedOptions);
        $this->assertEquals($expectedChoices, $resolvedOptions['choices']);
    }

    public function testConfigureWithFetchFromCache()
    {
        $this->router->expects($this->never())
            ->method('getRouteCollection');

        $this->readerRegistry
            ->expects($this->never())
            ->method($this->anything());

        $this->cache->expects($this->once())
            ->method('contains')
            ->willReturn(true);
        $this->cache->expects($this->never())
            ->method('save');
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($this->isType('string'))
            ->willReturn(
                [
                    'oro_route_get_simple',
                    'oro_route_get',
                    'oro_route_get_post',
                    'oro_route_with_option',
                    'oro_route_get_simple_no_title'
                ]
            );

        $options = ['add_titles' => false, 'menu_name' => 'menu'];

        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve($options);

        $expectedChoices = [
            'Oro Route Get Simple' => 'oro_route_get_simple',
            'Oro Route Get' => 'oro_route_get',
            'Oro Route Get Post' => 'oro_route_get_post',
            'Oro Route With Option' => 'oro_route_with_option',
            'Oro Route Get Simple No Title' => 'oro_route_get_simple_no_title',
        ];

        $this->assertArrayHasKey('choices', $resolvedOptions);
        $this->assertEquals($expectedChoices, $resolvedOptions['choices']);
    }

    /**
     * @return RouteCollection
     */
    protected function getRouteCollection()
    {
        $routeCollection = new RouteCollection();

        $simpleRoute = new Route('/simple');
        $routeCollection->add('oro_route_get_simple', $simpleRoute);

        $specialName = new Route('/special/simple');
        $routeCollection->add('special_route_get_simple', $specialName);

        $getRoute = new Route('/get');
        $getRoute->setMethods(['GET']);
        $routeCollection->add('oro_route_get', $getRoute);

        $getPostRoute = new Route('/get-post');
        $getPostRoute->setMethods(['GET', 'POST']);
        $routeCollection->add('oro_route_get_post', $getPostRoute);

        $postRoute = new Route('/post');
        $postRoute->setMethods(['POST']);
        $routeCollection->add('oro_route_post_simple', $postRoute);

        $withParameters = new Route('/parameter/{id}');
        $routeCollection->add('oro_route_with_parameters', $withParameters);

        $withOptions = new Route('/with-options');
        $withOptions->setOption('test', true);
        $routeCollection->add('oro_route_with_option', $withOptions);

        $simpleWithoutTitleRoute = new Route('/get/without-title');
        $routeCollection->add('oro_route_get_simple_no_title', $simpleWithoutTitleRoute);

        return $routeCollection;
    }
}
