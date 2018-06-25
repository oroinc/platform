<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\ContentProvider\ContentProviderManager;
use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\Event\Events;
use Oro\Bundle\UIBundle\Provider\UserAgentProviderInterface;
use Oro\Bundle\UIBundle\Twig\Template;
use Oro\Bundle\UIBundle\Twig\UiExtension;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class UiExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $environment;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $requestStack;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $contentProviderManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $userAgentProvider;

    /** @var UiExtension */
    protected $extension;

    protected function setUp()
    {
        $this->environment = $this->createMock(\Twig_Environment::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contentProviderManager = $this->getMockBuilder(ContentProviderManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->userAgentProvider = $this->createMock(UserAgentProviderInterface::class);

        $container = self::getContainerBuilder()
            ->add('event_dispatcher', $this->eventDispatcher)
            ->add('request_stack', $this->requestStack)
            ->add('oro_ui.content_provider.manager', $this->contentProviderManager)
            ->add('oro_ui.user_agent_provider', $this->userAgentProvider)
            ->getContainer($this);

        $this->extension = new UiExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_ui', $this->extension->getName());
    }

    public function testOnScrollDataBefore()
    {
        $pageIdentifier = 'test-page';
        $data = new ScrollData(['fields']);
        $alteredData = new ScrollData(['altered', 'fields']);
        $formView = $this->createMock(FormView::class);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                'oro_ui.scroll_data.before.' . $pageIdentifier,
                $this->isInstanceOf(BeforeListRenderEvent::class)
            )->willReturnCallback(
                function ($name, BeforeListRenderEvent $event) use ($data, $alteredData, $formView) {
                    $this->assertEquals($this->environment, $event->getEnvironment());
                    $this->assertEquals($data, $event->getScrollData());
                    $this->assertEquals($formView, $event->getFormView());
                    $event->setScrollData($alteredData);
                }
            );

        $entity = new \stdClass();

        $this->assertEquals(
            $alteredData->getData(),
            self::callTwigFunction(
                $this->extension,
                'oro_ui_scroll_data_before',
                [$this->environment, $pageIdentifier, $data->getData(), $entity, $formView]
            )
        );
    }

    public function testRenderBlock()
    {
        $testTemplate = 'testTemplate';
        $expected = 'result';
        $template = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->environment->expects(self::once())
            ->method('loadTemplate')
            ->with($testTemplate)
            ->willReturn($template);
        $template->expects(self::once())
            ->method('renderBlock')
            ->with('block', ['key' => 'value', 'extraKey' => 'value'])
            ->willReturn($expected);

        self::assertEquals(
            $expected,
            self::callTwigFunction(
                $this->extension,
                'render_block',
                [$this->environment, ['key' => 'value'], 'testTemplate', 'block', ['extraKey' => 'value']]
            )
        );
    }

    public function testProcessForm()
    {
        $entity = new \stdClass();
        $formData = ['test'];

        $formView = $this->getMockBuilder(FormView::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                Events::BEFORE_UPDATE_FORM_RENDER,
                $this->isInstanceOf(BeforeFormRenderEvent::class)
            )
            ->willReturnCallback(
                function ($eventName, BeforeFormRenderEvent $event) use ($formView, $formData, $entity) {
                    self::assertSame($formView, $event->getForm());
                    self::assertSame($formData, $event->getFormData());
                    self::assertSame($entity, $event->getEntity());
                }
            );

        $this->assertSame(
            $formData,
            self::callTwigFunction(
                $this->extension,
                'oro_form_process',
                [$this->environment, $formData, $formView, $entity]
            )
        );
    }

    public function testProcessFormWithoutEntity()
    {
        $formData = ['test'];

        $formView = $this->getMockBuilder(FormView::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                Events::BEFORE_UPDATE_FORM_RENDER,
                $this->isInstanceOf(BeforeFormRenderEvent::class)
            )
            ->willReturnCallback(
                function ($eventName, BeforeFormRenderEvent $event) use ($formView, $formData) {
                    self::assertSame($formView, $event->getForm());
                    self::assertSame($formData, $event->getFormData());
                    self::assertNull($event->getEntity());
                }
            );

        $this->assertSame(
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
    public function testGetContent($content, $additionalContent, $keys, $expected)
    {
        $this->contentProviderManager->expects($this->once())
            ->method('getContent')
            ->with($keys)
            ->will($this->returnValue($content));

        $this->assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'oro_get_content', [$additionalContent, $keys])
        );
    }

    public function contentDataProvider()
    {
        return [
            [
                'content'           => ['b' => 'c'],
                'additionalContent' => ['a' => 'b'],
                'keys'              => ['a', 'b', 'c'],
                'expected'          => ['a' => 'b', 'b' => 'c']
            ],
            [
                'content'           => ['b' => 'c'],
                'additionalContent' => null,
                'keys'              => null,
                'expected'          => ['b' => 'c']
            ],
        ];
    }

    /**
     * @dataProvider prepareJsTemplateContentProvider
     */
    public function testPrepareJsTemplateContent($content, $expectedContent)
    {
        $this->assertEquals(
            $expectedContent,
            self::callTwigFilter($this->extension, 'oro_js_template_content', [$content])
        );
    }

    public function prepareJsTemplateContentProvider()
    {
        return [
            'null'                                  => [
                null,
                null,
            ],
            'empty'                                 => [
                '',
                '',
            ],
            'no script, no js template'             => [
                '<div>test</div>',
                '<div>test</div>',
            ],
            'no script, with js template'           => [
                '<div><%= test %></div>',
                '<div><%= test %></div>',
            ],
            'with script, no js template'           => [
                '<script type="text/javascript">var a = 1;</script>',
                '<% print("<sc" + "ript") %> type="text/javascript">var a = 1;<% print("</sc" + "ript>") %>',
            ],
            'js template inside script'             => [
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
    public function testRegex($expected, $subject, $pattern, $replacement, $limit)
    {
        $this->assertEquals(
            $expected,
            self::callTwigFilter(
                $this->extension,
                'oro_preg_replace',
                [$subject, $pattern, $replacement, $limit]
            )
        );
    }

    public function pregReplaceProvider()
    {
        return [
            'pattern 1' => [
                'expected'    => 'aaaaa aaaaaabbccccccccaaaaad d d d d d d ddde',
                'subject'     => 'aaaaa   aaaaaabbccccccccaaaaad d d d   d      d d ddde',
                'pattern'     => '/(\s){2,}/',
                'replacement' => '$1',
                'limit'       => -1
            ],
            'pattern 2' => [
                'expected'    => '-asd-',
                'subject'     => '------------asd----------',
                'pattern'     => '/(-){2,}/',
                'replacement' => '$1',
                'limit'       => -1,
            ],
            'pattern 3' => [
                'expected'    => '-asd-',
                'subject'     => '-asd----------',
                'pattern'     => '/(-){2,}/',
                'replacement' => '$1',
                'limit'       => 1,
            ],
        ];
    }

    /**
     * @dataProvider addUrlQueryProvider
     */
    public function testAddUrlQuery($expected, $source, array $query = null)
    {
        $request = new Request(null !== $query ? $query : []);

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'oro_url_add_query', [$source])
        );
    }

    public function addUrlQueryProvider()
    {
        return [
            'no request'                                                 => [
                'expected' => 'http://test.url/',
                'source'   => 'http://test.url/',
            ],
            'no query params'                                            => [
                'expected' => 'http://test.url/',
                'source'   => 'http://test.url/',
                'query'    => [],
            ],
            'no query params without host'                               => [
                'expected' => '/',
                'source'   => '/',
                'query'    => [],
            ],
            'same query params'                                          => [
                'expected' => 'http://test.url/?foo=1#bar',
                'source'   => 'http://test.url/?foo=1#bar',
                'query'    => ['foo' => 1],
            ],
            'same query params without host'                             => [
                'expected' => '/?foo=1#bar',
                'source'   => '/?foo=1#bar',
                'query'    => ['foo' => 1],
            ],
            'only new query params'                                      => [
                'expected' => 'http://test.url/?foo=1#bar',
                'source'   => 'http://test.url/#bar',
                'query'    => ['foo' => 1],
            ],
            'only new query params without host'                         => [
                'expected' => '/?foo=1#bar',
                'source'   => '/#bar',
                'query'    => ['foo' => 1],
            ],
            'existing and new query params'                              => [
                'expected' => 'http://test.url/?baz=2&foo=1#bar',
                'source'   => 'http://test.url/?foo=1#bar',
                'query'    => ['baz' => 2],
            ],
            'existing and new query params without host'                 => [
                'expected' => '/?baz=2&foo=1#bar',
                'source'   => '/?foo=1#bar',
                'query'    => ['baz' => 2],
            ],
            'existing and new query params without host with path'       => [
                'expected' => '/path/?baz=2&foo=1#bar',
                'source'   => '/path/?foo=1#bar',
                'query'    => ['baz' => 2],
            ],
            'existing and new query params without host with short path' => [
                'expected' => '/path?baz=2&foo=1#bar',
                'source'   => '/path?foo=1#bar',
                'query'    => ['baz' => 2],
            ],
        ];
    }

    /**
     * @dataProvider isUrlLocalProvider
     */
    public function testIsUrlLocal($expected, $server, $linkUrl)
    {
        $request = new Request();
        if (null !== $server) {
            $request->server->add($server);
        }

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'oro_is_url_local', [$linkUrl])
        );
    }

    public function isUrlLocalProvider()
    {
        return [
            'same page'                    => [
                'expected' => true,
                'server'   => [
                    'REQUEST_SCHEME' => 'http',
                    'SERVER_NAME'    => 'test.url',
                    'SERVER_PORT'    => 80,
                    'REQUEST_URI'    => '/info',
                ],
                'link_url' => 'http://test.url/info',
            ],
            'different path'               => [
                'expected' => true,
                'server'   => [
                    'REQUEST_SCHEME' => 'http',
                    'SERVER_NAME'    => 'test.url',
                    'SERVER_PORT'    => 80,
                    'REQUEST_URI'    => '/contact',
                ],
                'link_url' => 'http://test.url/info',
            ],
            'different host'               => [
                'expected' => false,
                'server'   => [
                    'REQUEST_SCHEME' => 'http',
                    'SERVER_NAME'    => 'test.com',
                    'SERVER_PORT'    => 80,
                    'REQUEST_URI'    => '/info',
                ],
                'link_url' => 'http://test.url/info',
            ],
            'different port'               => [
                'expected' => false,
                'server'   => [
                    'REQUEST_SCHEME' => 'http',
                    'SERVER_NAME'    => 'test.url',
                    'SERVER_PORT'    => 80,
                    'REQUEST_URI'    => '/info',
                ],
                'link_url' => 'http://test.url:8080/info',
            ],
            'link from secure to insecure' => [
                'expected' => false,
                'server'   => [
                    'REQUEST_SCHEME' => 'https',
                    'SERVER_NAME'    => 'test.url',
                    'SERVER_PORT'    => 443,
                    'REQUEST_URI'    => '/contact',
                    'HTTPS'          => 'on',
                ],
                'link_url' => 'http://test.url/info',
            ],
            'link from insecure to secure' => [
                'expected' => true,
                'server'   => [
                    'REQUEST_SCHEME' => 'http',
                    'SERVER_NAME'    => 'test.url',
                    'SERVER_PORT'    => 80,
                    'REQUEST_URI'    => '/contact',
                ],
                'link_url' => 'https://test.url/info',
            ],
        ];
    }

    public function testSortByWithDefaultOptions()
    {
        $result = self::callTwigFilter(
            $this->extension,
            'oro_sort_by',
            [
                [
                    ['name' => '1'],
                    ['name' => '2', 'priority' => 100],
                    ['name' => '3'],
                ]
            ]
        );
        $this->assertSame(
            [
                ['name' => '1'],
                ['name' => '3'],
                ['name' => '2', 'priority' => 100],
            ],
            $result
        );
    }

    public function testSortByReverse()
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
                    'reverse' => true
                ]
            ]
        );
        $this->assertSame(
            [
                ['name' => '2', 'priority' => 100],
                ['name' => '1'],
                ['name' => '3'],
            ],
            $result
        );
    }

    public function testSortByString()
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
                    'property'     => 'name',
                    'sorting-type' => 'string'
                ]
            ]
        );
        $this->assertSame(
            [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ],
            $result
        );
    }

    public function testSortByStringCaseInsensitive()
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
                    'property'     => 'name',
                    'sorting-type' => 'string-case'
                ]
            ]
        );
        $this->assertSame(
            [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'C'],
            ],
            $result
        );
    }

    /**
     * @dataProvider skypeButtonProvider
     */
    public function testGetSkypeButton($username, $options, $expectedOptions, $expectedTemplate)
    {
        $this->environment->expects($this->once())
            ->method('render')
            ->with($expectedTemplate, $this->anything())
            ->will(
                $this->returnCallback(
                    function ($template, $options) use ($expectedOptions, $username) {
                        \PHPUnit\Framework\TestCase::assertArrayHasKey('name', $options['options']);
                        \PHPUnit\Framework\TestCase::assertEquals(
                            $expectedOptions['name'],
                            $options['options']['name']
                        );
                        \PHPUnit\Framework\TestCase::assertArrayHasKey('participants', $options['options']);
                        \PHPUnit\Framework\TestCase::assertEquals(
                            $expectedOptions['participants'],
                            $options['options']['participants']
                        );
                        \PHPUnit\Framework\TestCase::assertArrayHasKey('element', $options['options']);
                        \PHPUnit\Framework\TestCase::assertContains(
                            'skype_button_' . md5($username),
                            $options['options']['element']
                        );

                        return 'BUTTON_CODE';
                    }
                )
            );
        $this->assertEquals(
            'BUTTON_CODE',
            self::callTwigFunction($this->extension, 'skype_button', [$this->environment, $username, $options])
        );
    }

    public function skypeButtonProvider()
    {
        return [
            [
                'echo123',
                [],
                [
                    'participants' => ['echo123'],
                    'name'         => 'call',
                ],
                UiExtension::SKYPE_BUTTON_TEMPLATE
            ],
            [
                'echo123',
                [
                    'participants' => ['test'],
                    'name'         => 'chat',
                    'template'     => 'test_template'
                ],
                [
                    'participants' => ['test'],
                    'name'         => 'chat',
                ],
                'test_template'
            ]
        ];
    }

    /**
     * @dataProvider ceilProvider
     */
    public function testCeil($expected, $testValue)
    {
        $this->assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'ceil', [$testValue])
        );
    }

    public function ceilProvider()
    {
        return [
            [5, 4.6],
            [5, 4.1]
        ];
    }
}
