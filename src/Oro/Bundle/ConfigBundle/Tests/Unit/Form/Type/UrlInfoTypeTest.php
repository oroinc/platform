<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Form\Type\UrlInfoType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class UrlInfoTypeTest extends FormIntegrationTestCase
{
    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([new UrlInfoType($this->router)], [])
        ];
    }

    /**
     * @dataProvider formData
     */
    public function testForm(string $expected, ?string $route, ?array $routeParams, ?int $urlType): void
    {
        $expectedRoute = $route ?? UrlInfoType::DEFAULT_DISPLAY_ROUTE;
        $expectedRouteParams = $routeParams ?? UrlInfoType::DEFAULT_DISPLAY_ROUTE_PARAMS;
        $expectedUrlType = $urlType ?? UrlGeneratorInterface::ABSOLUTE_URL;
        $options = [];
        if (null !== $route) {
            $options['route'] = $route;
        }
        if (null !== $routeParams) {
            $options['route_params'] = $routeParams;
        }
        if (null !== $urlType) {
            $options['url_type'] = $urlType;
        }

        $this->router->expects(self::once())
            ->method('generate')
            ->with($expectedRoute, $expectedRouteParams, $expectedUrlType)
            ->willReturn($expected);

        $field = $this->factory->create(UrlInfoType::class, null, $options);
        $view = $field->createView();

        self::assertEquals($expected, $view->vars['value']);
    }

    public function formData(): array
    {
        return [
            [
                'https://example.com/route',
                'route_1',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ],
            [
                '/route/param1/value1',
                'route_1',
                ['param1' => 'value1'],
                UrlGeneratorInterface::RELATIVE_PATH
            ],
            [
                'https://example.com/route',
                null,
                null,
                null
            ]
        ];
    }
}
