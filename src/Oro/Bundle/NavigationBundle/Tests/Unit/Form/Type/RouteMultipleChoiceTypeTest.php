<?php

declare(strict_types=1);

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType;
use Oro\Bundle\NavigationBundle\Form\Type\RouteMultipleChoiceType;
use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;
use Oro\Bundle\NavigationBundle\Provider\TitleTranslator;
use Oro\Bundle\NavigationBundle\Title\TitleReader\TitleReaderRegistry;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class RouteMultipleChoiceTypeTest extends FormIntegrationTestCase
{
    private RouterInterface&MockObject $router;
    private TitleReaderRegistry&MockObject $readerRegistry;
    private TitleTranslator&MockObject $translator;
    private TitleServiceInterface&MockObject $titleService;
    private CacheInterface&MockObject $cache;
    private RouteMultipleChoiceType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->readerRegistry = $this->createMock(TitleReaderRegistry::class);
        $this->translator = $this->createMock(TitleTranslator::class);
        $this->titleService = $this->createMock(TitleServiceInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->formType = new RouteMultipleChoiceType(
            $this->router,
            $this->readerRegistry,
            $this->translator,
            $this->titleService,
            $this->cache
        );

        parent::setUp();
    }

    public function testExtendsRouteChoiceType(): void
    {
        self::assertInstanceOf(RouteChoiceType::class, $this->formType);
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals('oro_route_multiple_choice', $this->formType->getBlockPrefix());
    }

    public function testConfigureOptionsAddsMultipleAndPlaceholder(): void
    {
        $routeCollection = $this->getRouteCollection();

        $this->router->expects(self::once())
            ->method('getRouteCollection')
            ->willReturn($routeCollection);

        $this->cache->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve(['menu_name' => 'test_menu']);

        self::assertTrue($resolvedOptions['multiple']);
        self::assertArrayHasKey('configs', $resolvedOptions);
        self::assertEquals(
            'oro.navigation.route.form.placeholder_multiple',
            $resolvedOptions['configs']['placeholder']
        );
        self::assertTrue($resolvedOptions['configs']['allowClear']);
    }

    private function getRouteCollection(): RouteCollection
    {
        $routeCollection = new RouteCollection();
        $route = new Route('/test');
        $routeCollection->add('oro_test_route', $route);
        return $routeCollection;
    }
}
