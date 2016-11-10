<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\NavigationBundle\Entity\Repository\TitleRepository;
use Oro\Bundle\NavigationBundle\Entity\Title;
use Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class RouteChoiceTypeTest extends FormIntegrationTestCase
{
    /**
     * @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $router;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $translator;

    /**
     * @var RouteChoiceType
     */
    private $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {

        $this->translator = $this->getTranslator();
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->router = $this->getMock(RouterInterface::class);

        $this->formType = new RouteChoiceType($this->router, $this->registry, $this->translator);

        parent::setUp();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_route_choice', $this->formType->getName());
        $this->assertEquals('oro_route_choice', $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals('genemu_jqueryselect2_choice', $this->formType->getParent());
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array $options
     * @param array $expectedRoutes
     * @param array $expectedChoices
     */
    public function testConfigureOptionsDefaultOptions(array $options, array $expectedRoutes, array $expectedChoices)
    {
        $routeCollection = $this->getRouteCollection();

        $this->router->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn($routeCollection);

        $this->getRepositoryMock($expectedRoutes);

        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve($options);

        $this->assertArrayHasKey('choices', $resolvedOptions);
        $this->assertEquals($expectedChoices, $resolvedOptions['choices']);
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

        $this->registry->expects($this->never())
            ->method($this->anything());

        $options = ['add_titles' => false];

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

    /**
     * @param array $filteredRoutes
     */
    protected function getRepositoryMock(array $filteredRoutes)
    {
        /** @var TitleRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->getMockBuilder(TitleRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->once())
            ->method('getTitles')
            ->with($filteredRoutes)
            ->willReturn(
                [
                    ['route' => 'oro_route_get_simple', 'shortTitle' => 'Get Simple'],
                    ['route' => 'special_route_get_simple', 'shortTitle'  => 'Special Get Simple'],
                    ['route' => 'oro_route_get', 'shortTitle'  => 'Get'],
                    ['route' => 'oro_route_get_post', 'shortTitle'  => 'Get Post'],
                    ['route' => 'oro_route_post_simple', 'shortTitle'  => 'Post Simple'],
                    ['route' => 'oro_route_with_parameters', 'shortTitle'  => 'With Parameters'],
                    ['route' => 'oro_route_with_option', 'shortTitle'  => 'With Option']
                ]
            );

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Title::class)
            ->willReturn($repo);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Title::class)
            ->willReturn($em);
    }
}
