<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider;
use Oro\Bundle\UIBundle\Twig\PlaceholderExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Twig\Environment;

class PlaceholderExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private const PLACEHOLDER_NAME = 'placeholder_name';
    private const INVALID_PLACEHOLDER_NAME = 'invalid_placeholder_name';
    private const TEMPLATE_NAME = '@FooBar/Test/test.html.twig';
    private const ACTION_NAME = 'Foo\Bundle\BarBundle\Controller\TestController::testAction';
    private const DELIMITER = '<br/>';

    private const array PLACEHOLDERS = [
        self::PLACEHOLDER_NAME => [
            'items' => [
                ['template' => self::TEMPLATE_NAME],
                ['action' => self::ACTION_NAME]
            ]
        ],
        self::INVALID_PLACEHOLDER_NAME => [
            'items' => [
                ['foo' => 'bar', 'baz' => 'bar'],
            ]
        ]
    ];

    private Environment&MockObject $environment;
    private PlaceholderProvider&MockObject $placeholderProvider;
    private FragmentHandler&MockObject $fragmentHandler;
    private RequestStack&MockObject $requestStack;
    private PlaceholderExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);
        $this->placeholderProvider = $this->createMock(PlaceholderProvider::class);
        $this->fragmentHandler = $this->createMock(FragmentHandler::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $container = self::getContainerBuilder()
            ->add(PlaceholderProvider::class, $this->placeholderProvider)
            ->add(FragmentHandler::class, $this->fragmentHandler)
            ->add(RequestStack::class, $this->requestStack)
            ->getContainer($this);

        $this->extension = new PlaceholderExtension($container);
    }

    public function testRenderPlaceholder(): void
    {
        $variables = ['variables' => 'test'];
        $query = ['key' => 'value'];
        $expectedTemplateRender = '<p>template</p>';
        $expectedActionRender = '<p>action</p>';
        $expectedResult = $expectedTemplateRender . self::DELIMITER . $expectedActionRender;

        $request = new Request();
        $request->query->add($query);
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->placeholderProvider->expects(self::once())
            ->method('getPlaceholderItems')
            ->with(self::PLACEHOLDER_NAME, $variables)
            ->willReturn(self::PLACEHOLDERS[self::PLACEHOLDER_NAME]['items']);

        $this->environment->expects(self::once())
            ->method('render')
            ->with(self::TEMPLATE_NAME, $variables)
            ->willReturn($expectedTemplateRender);

        $this->fragmentHandler->expects(self::once())
            ->method('render')
            ->willReturn($expectedActionRender);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFunction($this->extension, 'placeholder', [
                $this->environment,
                [],
                self::PLACEHOLDER_NAME,
                $variables,
                ['delimiter' => self::DELIMITER]
            ])
        );
    }

    public function testRenderPlaceholderFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot render placeholder item with keys "bar", "bar". Expects "template" or "action" key.'
        );

        $this->placeholderProvider->expects(self::once())
            ->method('getPlaceholderItems')
            ->with(self::INVALID_PLACEHOLDER_NAME, [])
            ->willReturn(self::PLACEHOLDERS[self::INVALID_PLACEHOLDER_NAME]['items']);

        self::callTwigFunction($this->extension, 'placeholder', [
            $this->environment,
            [],
            self::INVALID_PLACEHOLDER_NAME,
            [],
            ['delimiter' => self::DELIMITER]
        ]);
    }
}
