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
        $this->assertCount(1, $functions);

        /** @var \Twig_SimpleFunction $function */
        $function = current($functions);
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('oro_url_add_query', $function->getName());
        $this->assertEquals([$this->extension, 'addQuery'], $function->getCallable());
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
}
