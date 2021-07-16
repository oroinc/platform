<?php

namespace Oro\Bundle\MicrosoftIntegrationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\MicrosoftIntegrationBundle\Form\Type\UrlInfoType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Routing\RouterInterface;

class UrlInfoTypeTest extends FormIntegrationTestCase
{
    /** @var RouterInterface|MockObject */
    protected $router;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->router = $this->getMockBuilder(RouterInterface::class)->getMock();
        parent::setUp();
    }

    /**
     * @return array|PreloadedExtension[]
     */
    protected function getExtensions()
    {
        $urlInfoType = new UrlInfoType($this->router);

        return [
            new PreloadedExtension(
                [
                    UrlInfoType::class => $urlInfoType
                ],
                []
            )
        ];
    }

    /**
     * @dataProvider formData
     */
    public function testForm(string $expected, ?string $route, ?array $routeParams, ?int $urlType): void
    {
        $expectedRoute = $route ?? UrlInfoType::DEFAULT_DISPLAY_ROUTE;
        $expectedRouteParams = $routeParams ?? UrlInfoType::DEFAULT_DISPLAY_ROUTE_PARAMS;
        $expectedUrlType = $urlType ?? RouterInterface::ABSOLUTE_URL;
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

    /**
     * @return array[]
     */
    public function formData(): array
    {
        return [
            [
                'https://example.com/route',
                'route_1',
                [],
                RouterInterface::ABSOLUTE_URL
            ],
            [
                '/route/param1/value1',
                'route_1',
                ['param1' => 'value1'],
                RouterInterface::RELATIVE_PATH
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
