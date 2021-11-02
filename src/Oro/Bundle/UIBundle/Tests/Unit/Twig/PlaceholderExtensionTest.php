<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider;
use Oro\Bundle\UIBundle\Twig\PlaceholderExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Twig\Environment;

class PlaceholderExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    private const PLACEHOLDER_NAME = 'placeholder_name';
    private const INVALID_PLACEHOLDER_NAME = 'invalid_placeholder_name';
    private const TEMPLATE_NAME = '@FooBar/Test/test.html.twig';
    private const ACTION_NAME = 'Foo\Bundle\BarBundle\Controller\TestController::testAction';
    private const DELIMITER = '<br/>';

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $environment;

    /** @var PlaceholderProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $placeholderProvider;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var FragmentHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $fragmentHandler;

    /** @var PlaceholderExtension */
    private $extension;

    private array $placeholders = [
        self::PLACEHOLDER_NAME => [
            'items' => [
                ['template' => self::TEMPLATE_NAME],
                ['action' => self::ACTION_NAME],
            ]
        ],
        self::INVALID_PLACEHOLDER_NAME => [
            'items' => [
                ['foo' => 'bar', 'baz' => 'bar'],
            ]
        ],
    ];

    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);
        $this->placeholderProvider = $this->createMock(PlaceholderProvider::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->fragmentHandler = $this->createMock(FragmentHandler::class);

        $container = self::getContainerBuilder()
            ->add('oro_ui.placeholder.provider', $this->placeholderProvider)
            ->add(RequestStack::class, $this->requestStack)
            ->add('fragment.handler', $this->fragmentHandler)
            ->getContainer($this);

        $this->extension = new PlaceholderExtension($container);
    }

    public function testRenderPlaceholder()
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

        $this->placeholderProvider->expects($this->once())
            ->method('getPlaceholderItems')
            ->with(self::PLACEHOLDER_NAME, $variables)
            ->willReturn($this->placeholders[self::PLACEHOLDER_NAME]['items']);

        $this->environment->expects($this->once())
            ->method('render')
            ->with(self::TEMPLATE_NAME, $variables)
            ->willReturn($expectedTemplateRender);

        $this->fragmentHandler->expects($this->once())
            ->method('render')
            ->willReturn($expectedActionRender);

        $result = self::callTwigFunction(
            $this->extension,
            'placeholder',
            [
                $this->environment,
                self::PLACEHOLDER_NAME,
                $variables,
                ['delimiter' => self::DELIMITER]
            ]
        );

        $this->assertEquals($expectedResult, $result);
    }

    public function testRenderPlaceholderFails()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot render placeholder item with keys "bar", "bar". Expects "template" or "action" key.'
        );

        $this->placeholderProvider->expects($this->once())
            ->method('getPlaceholderItems')
            ->with(self::INVALID_PLACEHOLDER_NAME, [])
            ->willReturn($this->placeholders[self::INVALID_PLACEHOLDER_NAME]['items']);

        self::callTwigFunction(
            $this->extension,
            'placeholder',
            [
                $this->environment,
                self::INVALID_PLACEHOLDER_NAME,
                [],
                ['delimiter' => self::DELIMITER]
            ]
        );
    }
}
