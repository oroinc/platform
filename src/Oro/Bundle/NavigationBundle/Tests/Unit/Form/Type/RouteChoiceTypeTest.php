<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType;
use Oro\Bundle\NavigationBundle\Provider\TitleService;
use Oro\Bundle\NavigationBundle\Provider\TitleTranslator;
use Oro\Bundle\NavigationBundle\Title\TitleReader\TitleReaderRegistry;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\TestUtils\Mocks\ServiceLink;

class RouteChoiceTypeTest extends FormIntegrationTestCase
{
    /**
     * @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $router;

    /**
     * @var TitleReaderRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $readerRegistry;

    /**
     * @var TitleTranslator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $translator;

    /**
     * @var TitleService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $titleService;

    /**
     * @var RouteChoiceType
     */
    private $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->readerRegistry = $this->createMock(TitleReaderRegistry::class);
        $this->translator = $this->createMock(TitleTranslator::class);
        $this->titleService = $this->createMock(TitleService::class);

        $this->formType = new RouteChoiceType(
            $this->router,
            $this->readerRegistry,
            $this->translator,
            new ServiceLink($this->titleService)
        );

        parent::setUp();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_route_choice', $this->formType->getName());
        $this->assertEquals('oro_route_choice', $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_select2_choice', $this->formType->getParent());
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
    {
        return [
            'default options' => [
                [],
                [
                    'oro_route_get_simple',
                    'oro_route_get',
                    'oro_route_get_post',
                    'oro_route_with_option',
                    'oro_route_get_simple_no_title'
                ],
                [
                    'oro_route_get_simple' => 'Oro Route Get Simple (Get Simple)',
                    'oro_route_get' => 'Oro Route Get (Get)',
                    'oro_route_get_post' => 'Oro Route Get Post (Get Post)',
                    'oro_route_with_option' => 'Oro Route With Option (With Option)'
                ]
            ],
            'filtered path' => [
                [
                    'path_filter' => '/^\/get/'
                ],
                [
                    'oro_route_get',
                    'oro_route_get_post',
                    'oro_route_get_simple_no_title'
                ],
                [
                    'oro_route_get' => 'Oro Route Get (Get)',
                    'oro_route_get_post' => 'Oro Route Get Post (Get Post)',
                ]
            ],
            'filtered name' => [
                [
                    'name_filter' => '/^oro_route_get/'
                ],
                [
                    'oro_route_get_simple',
                    'oro_route_get',
                    'oro_route_get_post',
                    'oro_route_get_simple_no_title'
                ],
                [
                    'oro_route_get_simple' => 'Oro Route Get Simple (Get Simple)',
                    'oro_route_get' => 'Oro Route Get (Get)',
                    'oro_route_get_post' => 'Oro Route Get Post (Get Post)',
                ]
            ],
            'include with parameters' => [
                [
                    'without_parameters_only' => false
                ],
                [
                    'oro_route_get_simple',
                    'oro_route_get',
                    'oro_route_get_post',
                    'oro_route_with_parameters',
                    'oro_route_with_option',
                    'oro_route_get_simple_no_title'
                ],
                [
                    'oro_route_get_simple' => 'Oro Route Get Simple (Get Simple)',
                    'oro_route_get' => 'Oro Route Get (Get)',
                    'oro_route_get_post' => 'Oro Route Get Post (Get Post)',
                    'oro_route_with_parameters' => 'Oro Route With Parameters (With Parameters)',
                    'oro_route_with_option' => 'Oro Route With Option (With Option)'
                ]
            ],
            'include without titles' => [
                [
                    'with_titles_only' => false
                ],
                [
                    'oro_route_get_simple',
                    'oro_route_get',
                    'oro_route_get_post',
                    'oro_route_with_option',
                    'oro_route_get_simple_no_title'
                ],
                [
                    'oro_route_get_simple' => 'Oro Route Get Simple (Get Simple)',
                    'oro_route_get' => 'Oro Route Get (Get)',
                    'oro_route_get_post' => 'Oro Route Get Post (Get Post)',
                    'oro_route_with_option' => 'Oro Route With Option (With Option)',
                    'oro_route_get_simple_no_title' => 'Oro Route Get Simple No Title'
                ]
            ],
        ];
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

        $options = ['add_titles' => false, 'menu_name' => 'menu'];

        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve($options);

        $expectedChoices = [
            'oro_route_get_simple' => 'Oro Route Get Simple',
            'oro_route_get' => 'Oro Route Get',
            'oro_route_get_post' => 'Oro Route Get Post',
            'oro_route_with_option' => 'Oro Route With Option',
            'oro_route_get_simple_no_title' => 'Oro Route Get Simple No Title'
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
