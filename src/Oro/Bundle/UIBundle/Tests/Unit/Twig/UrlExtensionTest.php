<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\UIBundle\Twig\UrlExtension;

class UrlExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UrlExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new UrlExtension();
    }

    public function testGetName()
    {
        $this->assertEquals(UrlExtension::NAME, $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(2, $functions);

        /** @var \Twig_SimpleFunction $function */
        $function = $functions[0];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('oro_url_add_query', $function->getName());
        $this->assertEquals([$this->extension, 'addQuery'], $function->getCallable());

        /** @var \Twig_SimpleFunction $function */
        $function = $functions[1];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('oro_is_url_local', $function->getName());
        $this->assertEquals([$this->extension, 'isUrlLocal'], $function->getCallable());
    }

    /**
     * @param string $expected
     * @param string $source
     * @param array|null $query
     * @dataProvider addQueryDataProvider
     */
    public function testAddQuery($expected, $source, array $query = null)
    {
        if (null !== $query) {
            $request = new Request($query);
            $this->extension->setRequest($request);
        }

        $this->assertEquals($expected, $this->extension->addQuery($source));
    }

    /**
     * @return array
     */
    public function addQueryDataProvider()
    {
        return [
            'no request' => [
                'expected' => 'http://test.url/',
                'source'   => 'http://test.url/',
            ],
            'no query params' => [
                'expected' => 'http://test.url/',
                'source'   => 'http://test.url/',
                'query'    => [],
            ],
            'no query params without host' => [
                'expected' => '/',
                'source'   => '/',
                'query'    => [],
            ],
            'same query params' => [
                'expected' => 'http://test.url/?foo=1#bar',
                'source'   => 'http://test.url/?foo=1#bar',
                'query'    => ['foo' => 1],
            ],
            'same query params without host' => [
                'expected' => '/?foo=1#bar',
                'source'   => '/?foo=1#bar',
                'query'    => ['foo' => 1],
            ],
            'only new query params' => [
                'expected' => 'http://test.url/?foo=1#bar',
                'source'   => 'http://test.url/#bar',
                'query'    => ['foo' => 1],
            ],
            'only new query params without host' => [
                'expected' => '/?foo=1#bar',
                'source'   => '/#bar',
                'query'    => ['foo' => 1],
            ],
            'existing and new query params' => [
                'expected' => 'http://test.url/?baz=2&foo=1#bar',
                'source'   => 'http://test.url/?foo=1#bar',
                'query'    => ['baz' => 2],
            ],
            'existing and new query params without host' => [
                'expected' => '/?baz=2&foo=1#bar',
                'source'   => '/?foo=1#bar',
                'query'    => ['baz' => 2],
            ],
            'existing and new query params without host with path' => [
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
     * @param string $expected
     * @param string $server
     * @param string $linkUrl
     * @dataProvider isUrlLocalProvider
     */
    public function testIsUrlLocal($expected, $server, $linkUrl)
    {
        if (null !== $server) {
            $request = new Request();
            $request->server->add($server);
            $this->extension->setRequest($request);
        }

        $this->assertEquals($expected, $this->extension->isUrlLocal($linkUrl));
    }

    /**
     * @return array
     */
    public function isUrlLocalProvider()
    {
        return [
            'same page' => [
                'expected' => true,
                'server'   => [
                    'REQUEST_SCHEME' => 'http',
                    'SERVER_NAME'    => 'test.url',
                    'SERVER_PORT'    => 80,
                    'REQUEST_URI'    => '/info',
                ],
                'link_url' => 'http://test.url/info',
            ],
            'different path' => [
                'expected' => true,
                'server'   => [
                    'REQUEST_SCHEME' => 'http',
                    'SERVER_NAME'    => 'test.url',
                    'SERVER_PORT'    => 80,
                    'REQUEST_URI'    => '/contact',
                ],
                'link_url' => 'http://test.url/info',
            ],
            'different host' => [
                'expected' => false,
                'server'   => [
                    'REQUEST_SCHEME' => 'http',
                    'SERVER_NAME'    => 'test.com',
                    'SERVER_PORT'    => 80,
                    'REQUEST_URI'    => '/info',
                ],
                'link_url' => 'http://test.url/info',
            ],
            'different port' => [
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
}
