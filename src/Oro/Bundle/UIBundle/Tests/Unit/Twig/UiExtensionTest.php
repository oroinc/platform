<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\ContentProvider\TwigContentProviderManager;
use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\Event\Events;
use Oro\Bundle\UIBundle\Provider\UserAgentProviderInterface;
use Oro\Bundle\UIBundle\Twig\UiExtension;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Template;
use Twig\TemplateWrapper;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class UiExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    private Environment|\PHPUnit\Framework\MockObject\MockObject $environment;

    private TwigContentProviderManager|\PHPUnit\Framework\MockObject\MockObject $contentProviderManager;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack;

    private RouterInterface|\PHPUnit\Framework\MockObject\MockObject $router;

    private UiExtension $extension;

    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);
        $this->contentProviderManager = $this->createMock(TwigContentProviderManager::class);
        $userAgentProvider = $this->createMock(UserAgentProviderInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->router = $this->createMock(RouterInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_ui.content_provider.manager.twig', $this->contentProviderManager)
            ->add('oro_ui.user_agent_provider', $userAgentProvider)
            ->add(EventDispatcherInterface::class, $this->eventDispatcher)
            ->add(RequestStack::class, $this->requestStack)
            ->add(RouterInterface::class, $this->router)
            ->getContainer($this);

        $this->extension = new UiExtension($container);
    }

    public function testOnScrollDataBefore(): void
    {
        $pageIdentifier = 'test-page';
        $data = new ScrollData(['fields']);
        $alteredData = new ScrollData(['altered', 'fields']);
        $formView = $this->createMock(FormView::class);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(BeforeListRenderEvent::class),
                'oro_ui.scroll_data.before.' . $pageIdentifier
            )
            ->willReturnCallback(function (BeforeListRenderEvent $event) use ($data, $alteredData, $formView) {
                self::assertEquals($this->environment, $event->getEnvironment());
                self::assertEquals($data, $event->getScrollData());
                self::assertEquals($formView, $event->getFormView());
                $event->setScrollData($alteredData);

                return $event;
            });

        $entity = new \stdClass();

        self::assertEquals(
            $alteredData->getData(),
            self::callTwigFunction(
                $this->extension,
                'oro_ui_scroll_data_before',
                [$this->environment, $pageIdentifier, $data->getData(), $entity, $formView]
            )
        );
    }

    public function testRenderBlock(): void
    {
        $testTemplate = 'testTemplate';
        $expected = 'result';
        $template = $this->createMock(Template::class);

        $this->environment->expects(self::once())
            ->method('load')
            ->with($testTemplate)
            ->willReturn(new TemplateWrapper($this->environment, $template));

        $this->environment->expects(self::once())
            ->method('mergeGlobals')
            ->with(['key' => 'value', 'extraKey' => 'value'])
            ->willReturn(['key' => 'value', 'extraKey' => 'value']);

        $template->expects(self::once())
            ->method('displayBlock')
            ->with('block', ['key' => 'value', 'extraKey' => 'value'])
            ->willReturnCallback(function () use ($expected) {
                echo $expected;
            });

        self::assertEquals(
            $expected,
            self::callTwigFunction(
                $this->extension,
                'render_block',
                [$this->environment, ['key' => 'value'], 'testTemplate', 'block', ['extraKey' => 'value']]
            )
        );
    }

    public function testProcessForm(): void
    {
        $entity = new \stdClass();
        $formData = ['test'];

        $formView = $this->createMock(FormView::class);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(BeforeFormRenderEvent::class),
                Events::BEFORE_UPDATE_FORM_RENDER
            )
            ->willReturnCallback(function (BeforeFormRenderEvent $event) use ($formView, $formData, $entity) {
                self::assertSame($formView, $event->getForm());
                self::assertSame($formData, $event->getFormData());
                self::assertSame($entity, $event->getEntity());

                return $event;
            });

        self::assertSame(
            $formData,
            self::callTwigFunction(
                $this->extension,
                'oro_form_process',
                [$this->environment, $formData, $formView, $entity]
            )
        );
    }

    public function testProcessFormWithoutEntity(): void
    {
        $formData = ['test'];

        $formView = $this->createMock(FormView::class);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(BeforeFormRenderEvent::class),
                Events::BEFORE_UPDATE_FORM_RENDER
            )
            ->willReturnCallback(function (BeforeFormRenderEvent $event) use ($formView, $formData) {
                self::assertSame($formView, $event->getForm());
                self::assertSame($formData, $event->getFormData());
                self::assertNull($event->getEntity());

                return $event;
            });

        self::assertSame(
            $formData,
            self::callTwigFunction(
                $this->extension,
                'oro_form_process',
                [$this->environment, $formData, $formView]
            )
        );
    }

    /**
     * @dataProvider contentDataProvider
     */
    public function testGetContent($content, $additionalContent, $keys, $expected): void
    {
        $this->contentProviderManager->expects(self::once())
            ->method('getContent')
            ->with($keys)
            ->willReturn($content);

        self::assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'oro_get_content', [$additionalContent, $keys])
        );
    }

    public function contentDataProvider(): array
    {
        return [
            [
                'content' => ['b' => 'c'],
                'additionalContent' => ['a' => 'b'],
                'keys' => ['a', 'b', 'c'],
                'expected' => ['a' => 'b', 'b' => 'c'],
            ],
            [
                'content' => ['b' => 'c'],
                'additionalContent' => null,
                'keys' => null,
                'expected' => ['b' => 'c'],
            ],
        ];
    }

    /**
     * @dataProvider prepareJsTemplateContentProvider
     */
    public function testPrepareJsTemplateContent($content, $expectedContent): void
    {
        self::assertEquals(
            $expectedContent,
            self::callTwigFilter($this->extension, 'oro_js_template_content', [$content])
        );
    }

    public function prepareJsTemplateContentProvider(): array
    {
        return [
            'null' => [
                null,
                null,
            ],
            'empty' => [
                '',
                '',
            ],
            'no script, no js template' => [
                '<div>test</div>',
                '<div>test</div>',
            ],
            'no script, with js template' => [
                '<div><%= test %></div>',
                '<div><%= test %></div>',
            ],
            'with script, no js template' => [
                '<script type="text/javascript">var a = 1;</script>',
                '<% print("<sc" + "ript") %> type="text/javascript">var a = 1;<% print("</sc" + "ript>") %>',
            ],
            'js template inside script' => [
                '<script type="text/javascript">var a = "<%= var %>";</script>',
                '<% print("<sc" + "ript") %> type="text/javascript">'
                . 'var a = "<% print("<" + "%") %>= var <% print("%" + ">") %>";'
                . '<% print("</sc" + "ript>") %>',
            ],
            'js template inside and outside script' => [
                '<div><%= var %></div>' . "\n"
                . '<script type="text/javascript">var a = "<%= var %>";</script>' . "\n"
                . '<div><%= var %></div>' . "\n"
                . '<script>var a = "<%= var %>";</script>' . "\n"
                . 'some text',
                '<div><%= var %></div>' . "\n"
                . '<% print("<sc" + "ript") %> type="text/javascript">'
                . 'var a = "<% print("<" + "%") %>= var <% print("%" + ">") %>";'
                . '<% print("</sc" + "ript>") %>' . "\n"
                . '<div><%= var %></div>' . "\n"
                . '<% print("<sc" + "ript") %>>'
                . 'var a = "<% print("<" + "%") %>= var <% print("%" + ">") %>";'
                . '<% print("</sc" + "ript>") %>' . "\n"
                . 'some text',
            ],
        ];
    }

    /**
     * @dataProvider pregReplaceProvider
     */
    public function testRegex($expected, $subject, $pattern, $replacement, $limit): void
    {
        self::assertEquals(
            $expected,
            self::callTwigFilter(
                $this->extension,
                'oro_preg_replace',
                [$subject, $pattern, $replacement, $limit]
            )
        );
    }

    public function pregReplaceProvider(): array
    {
        return [
            'pattern 1' => [
                'expected' => 'aaaaa aaaaaabbccccccccaaaaad d d d d d d ddde',
                'subject' => 'aaaaa   aaaaaabbccccccccaaaaad d d d   d      d d ddde',
                'pattern' => '/(\s){2,}/',
                'replacement' => '$1',
                'limit' => -1,
            ],
            'pattern 2' => [
                'expected' => '-asd-',
                'subject' => '------------asd----------',
                'pattern' => '/(-){2,}/',
                'replacement' => '$1',
                'limit' => -1,
            ],
            'pattern 3' => [
                'expected' => '-asd-',
                'subject' => '-asd----------',
                'pattern' => '/(-){2,}/',
                'replacement' => '$1',
                'limit' => 1,
            ],
        ];
    }

    /**
     * @dataProvider addUrlQueryProvider
     */
    public function testAddUrlQuery($expected, $source, array $query = null): void
    {
        $request = new Request($query ?? []);

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        self::assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'oro_url_add_query', [$source])
        );
    }

    public function addUrlQueryProvider(): array
    {
        return [
            'no request' => [
                'expected' => 'http://test.url/',
                'source' => 'http://test.url/',
            ],
            'no query params' => [
                'expected' => 'http://test.url/',
                'source' => 'http://test.url/',
                'query' => [],
            ],
            'no query params without host' => [
                'expected' => '/',
                'source' => '/',
                'query' => [],
            ],
            'same query params' => [
                'expected' => 'http://test.url/?foo=1#bar',
                'source' => 'http://test.url/?foo=1#bar',
                'query' => ['foo' => 1],
            ],
            'same query params without host' => [
                'expected' => '/?foo=1#bar',
                'source' => '/?foo=1#bar',
                'query' => ['foo' => 1],
            ],
            'only new query params' => [
                'expected' => 'http://test.url/?foo=1#bar',
                'source' => 'http://test.url/#bar',
                'query' => ['foo' => 1],
            ],
            'only new query params without host' => [
                'expected' => '/?foo=1#bar',
                'source' => '/#bar',
                'query' => ['foo' => 1],
            ],
            'existing and new query params' => [
                'expected' => 'http://test.url/?baz=2&foo=1#bar',
                'source' => 'http://test.url/?foo=1#bar',
                'query' => ['baz' => 2],
            ],
            'existing and new query params without host' => [
                'expected' => '/?baz=2&foo=1#bar',
                'source' => '/?foo=1#bar',
                'query' => ['baz' => 2],
            ],
            'existing and new query params without host with path' => [
                'expected' => '/path/?baz=2&foo=1#bar',
                'source' => '/path/?foo=1#bar',
                'query' => ['baz' => 2],
            ],
            'existing and new query params without host with short path' => [
                'expected' => '/path?baz=2&foo=1#bar',
                'source' => '/path?foo=1#bar',
                'query' => ['baz' => 2],
            ],
        ];
    }

    /**
     * @dataProvider isUrlLocalProvider
     */
    public function testIsUrlLocal(bool $expected, array $server, string $linkUrl): void
    {
        $request = new Request();
        $request->server->add($server);

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        self::assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'oro_is_url_local', [$linkUrl])
        );
    }

    public function isUrlLocalProvider(): array
    {
        return [
            'same page' => [
                'expected' => true,
                'server' => [
                    'REQUEST_SCHEME' => 'http',
                    'SERVER_NAME' => 'test.url',
                    'SERVER_PORT' => 80,
                    'REQUEST_URI' => '/info',
                ],
                'link_url' => 'http://test.url/info',
            ],
            'different path' => [
                'expected' => true,
                'server' => [
                    'REQUEST_SCHEME' => 'http',
                    'SERVER_NAME' => 'test.url',
                    'SERVER_PORT' => 80,
                    'REQUEST_URI' => '/contact',
                ],
                'link_url' => 'http://test.url/info',
            ],
            'different host' => [
                'expected' => false,
                'server' => [
                    'REQUEST_SCHEME' => 'http',
                    'SERVER_NAME' => 'test.com',
                    'SERVER_PORT' => 80,
                    'REQUEST_URI' => '/info',
                ],
                'link_url' => 'http://test.url/info',
            ],
            'different port' => [
                'expected' => false,
                'server' => [
                    'REQUEST_SCHEME' => 'http',
                    'SERVER_NAME' => 'test.url',
                    'SERVER_PORT' => 80,
                    'REQUEST_URI' => '/info',
                ],
                'link_url' => 'http://test.url:8080/info',
            ],
            'link from secure to insecure' => [
                'expected' => false,
                'server' => [
                    'REQUEST_SCHEME' => 'https',
                    'SERVER_NAME' => 'test.url',
                    'SERVER_PORT' => 443,
                    'REQUEST_URI' => '/contact',
                    'HTTPS' => 'on',
                ],
                'link_url' => 'http://test.url/info',
            ],
            'link from insecure to secure' => [
                'expected' => true,
                'server' => [
                    'REQUEST_SCHEME' => 'http',
                    'SERVER_NAME' => 'test.url',
                    'SERVER_PORT' => 80,
                    'REQUEST_URI' => '/contact',
                ],
                'link_url' => 'https://test.url/info',
            ],
        ];
    }

    public function testSortByWithDefaultOptions(): void
    {
        $result = self::callTwigFilter(
            $this->extension,
            'oro_sort_by',
            [
                [
                    ['name' => '1'],
                    ['name' => '2', 'priority' => 100],
                    ['name' => '3'],
                ],
            ]
        );
        self::assertSame(
            [
                ['name' => '1'],
                ['name' => '3'],
                ['name' => '2', 'priority' => 100],
            ],
            $result
        );
    }

    public function testSortByReverse(): void
    {
        $result = self::callTwigFilter(
            $this->extension,
            'oro_sort_by',
            [
                [
                    ['name' => '1'],
                    ['name' => '2', 'priority' => 100],
                    ['name' => '3'],
                ],
                [
                    'reverse' => true,
                ],
            ]
        );
        self::assertSame(
            [
                ['name' => '2', 'priority' => 100],
                ['name' => '1'],
                ['name' => '3'],
            ],
            $result
        );
    }

    public function testSortByString(): void
    {
        $result = self::callTwigFilter(
            $this->extension,
            'oro_sort_by',
            [
                [
                    ['name' => 'a'],
                    ['name' => 'c'],
                    ['name' => 'b'],
                ],
                [
                    'property' => 'name',
                    'sorting-type' => 'string',
                ],
            ]
        );
        self::assertSame(
            [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ],
            $result
        );
    }

    public function testSortByStringCaseInsensitive(): void
    {
        $result = self::callTwigFilter(
            $this->extension,
            'oro_sort_by',
            [
                [
                    ['name' => 'a'],
                    ['name' => 'C'],
                    ['name' => 'b'],
                ],
                [
                    'property' => 'name',
                    'sorting-type' => 'string-case',
                ],
            ]
        );
        self::assertSame(
            [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'C'],
            ],
            $result
        );
    }

    public function testRenderContent(): void
    {
        self::assertSame(
            'render_content data',
            self::callTwigFilter(
                $this->extension,
                'render_content',
                [
                    'render_content data',
                ]
            )
        );
    }

    /**
     * @dataProvider skypeButtonProvider
     */
    public function testGetSkypeButton(
        string $username,
        array $options,
        array $expectedOptions,
        string $expectedTemplate
    ): void {
        $this->environment->expects(self::once())
            ->method('render')
            ->with($expectedTemplate, $this->anything())
            ->willReturnCallback(function ($template, $options) use ($expectedOptions, $username) {
                self::assertArrayHasKey('name', $options['options']);
                self::assertEquals($expectedOptions['name'], $options['options']['name']);
                self::assertArrayHasKey('participants', $options['options']);
                self::assertEquals($expectedOptions['participants'], $options['options']['participants']);
                self::assertArrayHasKey('element', $options['options']);
                self::assertStringContainsString('skype_button_' . md5($username), $options['options']['element']);

                return 'BUTTON_CODE';
            });
        self::assertEquals(
            'BUTTON_CODE',
            self::callTwigFunction($this->extension, 'skype_button', [$this->environment, $username, $options])
        );
    }

    public function skypeButtonProvider(): array
    {
        return [
            [
                'echo123',
                [],
                [
                    'participants' => ['echo123'],
                    'name' => 'call',
                ],
                '@OroUI/skype_button.html.twig',
            ],
            [
                'echo123',
                [
                    'participants' => ['test'],
                    'name' => 'chat',
                    'template' => 'test_template',
                ],
                [
                    'participants' => ['test'],
                    'name' => 'chat',
                ],
                'test_template',
            ],
        ];
    }

    /**
     * @dataProvider ceilProvider
     */
    public function testCeil(int $expected, float $testValue): void
    {
        self::assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'ceil', [$testValue])
        );
    }

    public function ceilProvider(): array
    {
        return [
            [5, 4.6],
            [5, 4.1],
        ];
    }

    /**
     * @dataProvider RenderAdditionalDataDataProvider
     */
    public function testRenderAdditionalData(FormView $formView, array $expectedResult): void
    {
        $actualResult = self::callTwigFunction(
            $this->extension,
            'oro_form_additional_data',
            [$this->environment, $formView, 'Sample Label']
        );

        self::assertSame($expectedResult, $actualResult);
    }

    public function renderAdditionalDataDataProvider(): array
    {
        $renderedChildView = new FormView();
        $renderedChildView->setRendered();
        $formViewWithRenderedChild = new FormView();
        $formViewWithRenderedChild->children = [$renderedChildView];

        $regularChildView = new FormView();
        $regularChildView->vars['extra_field'] = true;
        $regularChildView->vars['name'] = 'some_field_name';
        $formViewWithRegularChild = new FormView();
        $formViewWithRegularChild->children = [$regularChildView];

        $regularChildViewWithoutExtraField = new FormView();
        $regularChildViewWithoutExtraField->vars['name'] = 'some_field_name';
        $formViewWithRegularChildWithoutExtraField = new FormView();
        $formViewWithRegularChildWithoutExtraField->children = [$regularChildViewWithoutExtraField];

        $renderedExtraFieldChildView = new FormView();
        $renderedExtraFieldChildView->setRendered();
        $renderedExtraFieldChildView->vars['extra_field'] = true;
        $renderedExtraFieldChildView->vars['name'] = 'some_field_name';
        $formViewWithRenderedExtraFieldChild = new FormView();
        $formViewWithRenderedExtraFieldChild->children = [$renderedExtraFieldChildView];

        return [
            'form view without children' => [
                'formView' => new FormView(),
                'expectedResult' => [],
            ],
            'form view with already rendered child' => [
                'formView' => $formViewWithRenderedChild,
                'expectedResult' => [],
            ],
            'form view with regular child without extra_field' => [
                'formView' => $formViewWithRegularChildWithoutExtraField,
                'expectedResult' => [],
            ],
            'form view with regular child' => [
                'formView' => $formViewWithRegularChild,
                'expectedResult' => [
                    UiExtension::ADDITIONAL_SECTION_KEY => [
                        'title' => 'Sample Label',
                        'priority' => UiExtension::ADDITIONAL_SECTION_PRIORITY,
                        'subblocks' => [
                            [
                                'title' => '',
                                'useSpan' => false,
                                'data' => ['some_field_name' => ''],
                            ],
                        ],
                    ],
                ],
            ],
            'form view with already rendered extra field' => [
                'formView' => $formViewWithRenderedExtraFieldChild,
                'expectedResult' => [],
            ],
        ];
    }

    public function testRenderAdditionalDataAddsSectionWhenNoChildrenButHasAdditionalData(): void
    {
        $label = 'Sample Label';
        $additionalData = ['value' => 'Sample additional data'];
        $actualResult = self::callTwigFunction(
            $this->extension,
            'oro_form_additional_data',
            [$this->environment, new FormView(), $label, $additionalData]
        );

        self::assertEquals([
            UiExtension::ADDITIONAL_SECTION_KEY =>
                [
                    'title' => $label,
                    'priority' => UiExtension::ADDITIONAL_SECTION_PRIORITY,
                    'subblocks' => [
                        [
                            'title' => '',
                            'useSpan'=>false,
                            'data' => $additionalData,
                        ],
                    ],
                ],
        ], $actualResult);
    }

    public function testGetDefaultPage(): void
    {
        $url = 'http://sample-app/sample-url';
        $this->router->expects(self::once())
            ->method('generate')
            ->with('oro_default')
            ->willReturn($url);

        self::assertEquals(
            $url,
            self::callTwigFunction($this->extension, 'oro_default_page', [$this->environment])
        );
    }

    /**
     * @dataProvider urlAddQueryParametersDataProvider
     */
    public function testUrlAddQueryParameters(string $url, array $parameters, string $expected): void
    {
        self::assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'url_add_query_parameters', [$url, $parameters])
        );
    }

    public function urlAddQueryParametersDataProvider(): array
    {
        return [
            ['http://example.com/test', [], 'http://example.com/test'],
            [
                'https://example.com:8080/test',
                ['hello' => 2, 'second' => 'abc'],
                'https://example.com:8080/test?hello=2&second=abc',
            ],
            [
                'https://example.com:8080/test?hello=1&third=def',
                ['hello' => 2, 'second' => 'abc'],
                'https://example.com:8080/test?hello=2&third=def&second=abc',
            ],
            ['/test', ['hello' => 2, 'second' => 'abc'], '/test?hello=2&second=abc'],
        ];
    }
}
