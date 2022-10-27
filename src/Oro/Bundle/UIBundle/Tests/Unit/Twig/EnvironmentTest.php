<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Tests\Unit\Twig\Fixture\EnvironmentExtension;
use Oro\Bundle\UIBundle\Tests\Unit\Twig\Fixture\EnvironmentNodeVisitor;
use Oro\Bundle\UIBundle\Twig\Environment;
use Oro\Component\Testing\TempDirExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Twig\Cache\CacheInterface;
use Twig\Loader\ArrayLoader;
use Twig\Source;

/**
 * Copy of Twig_Tests_EnvironmentTest. Should be removed after merging of pull-request with this service changes.
 */
class EnvironmentTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;
    use TwigExtensionTestCaseTrait;

    public function testAutoescapeOption()
    {
        $loader = new ArrayLoader(
            [
                'html' => '{{ foo }} {{ foo }}',
                'js'   => '{{ bar }} {{ bar }}',
            ]
        );

        $twig = new Environment(
            $loader,
            [
                'debug'      => true,
                'cache'      => false,
                'autoescape' => [$this, 'escapingStrategyCallback'],
            ]
        );

        $this->assertEquals('foo&lt;br/ &gt; foo&lt;br/ &gt;', $twig->render('html', ['foo' => 'foo<br/ >']));
        $this->assertEquals(
            'foo\u003Cbr\/\u0020\u003E foo\u003Cbr\/\u0020\u003E',
            $twig->render('js', ['bar' => 'foo<br/ >'])
        );
    }

    public function escapingStrategyCallback(string $filename): string
    {
        return $filename;
    }

    public function testGlobals()
    {
        // globals can be added after calling getGlobals
        $twig = new Environment(new ArrayLoader());
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->addGlobal('foo', 'bar');
        $globals = $twig->getGlobals();
        $this->assertEquals('bar', $globals['foo']);

        // globals can be modified after runtime init
        $twig = new Environment(new ArrayLoader());
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->addGlobal('foo', 'bar');
        $globals = $twig->getGlobals();
        $this->assertEquals('bar', $globals['foo']);

        // globals can be modified after extensions init
        $twig = new Environment(new ArrayLoader());
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->getFunctions();
        $twig->addGlobal('foo', 'bar');
        $globals = $twig->getGlobals();
        $this->assertEquals('bar', $globals['foo']);

        // globals can be modified after extensions and runtime init
        $twig = new Environment(new ArrayLoader());
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->getFunctions();
        $twig->addGlobal('foo', 'bar');
        $globals = $twig->getGlobals();
        $this->assertEquals('bar', $globals['foo']);

        $twig = new Environment(new ArrayLoader(['test' => '{{foo}}']));
        $twig->getGlobals();
        $twig->addGlobal('foo', 'bar');
        $this->assertEquals('bar', $twig->render('test', []));
    }

    public function testExtensionsAreNotInitializedWhenRenderingACompiledTemplate()
    {
        $options = [
            'cache'       => $this->getTempDir('twig'),
            'auto_reload' => false,
            'debug'       => false
        ];

        // force compilation
        $twig = new Environment(new ArrayLoader(['test' => '{{ foo }}']), $options);
        $templateClass = $twig->getTemplateClass('test');
        $cache = $twig->getCache(false)->generateKey('test', $templateClass);

        if (!is_dir(dirname($cache))) {
            mkdir(dirname($cache), 0777, true);
        }

        $source = new Source('{{ foo }}', 'test');
        file_put_contents($cache, $twig->compileSource($source));

        // check that extensions won't be initialized when rendering a template that is already in the cache
        $twig = $this->getMockBuilder(Environment::class)
            ->setConstructorArgs([new ArrayLoader(['test' => '{{ foo }}']), $options])
            ->addMethods(['initExtensions'])
            ->getMock();

        $twig->expects($this->never())
            ->method('initExtensions');

        // render template
        $output = $twig->render('test', ['foo' => 'bar']);
        $this->assertEquals('bar', $output);

        unlink($cache);
    }

    public function testAddExtension()
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addExtension(new EnvironmentExtension());

        $this->assertArrayHasKey('foo_filter', $twig->getFilters());
        $this->assertArrayHasKey('foo_function', $twig->getFunctions());
        $this->assertArrayHasKey('foo_test', $twig->getTests());
        $this->assertArrayHasKey('foo_unary', $twig->getUnaryOperators());
        $this->assertArrayHasKey('foo_binary', $twig->getBinaryOperators());
        $this->assertArrayHasKey('foo_global', $twig->getGlobals());
        $visitors = $twig->getNodeVisitors();
        $this->assertInstanceOf(EnvironmentNodeVisitor::class, end($visitors));
    }

    public function testGenerateTemplateCache()
    {
        $templateName = __FUNCTION__;

        $loader = $this->getLoader();
        $loader->expects($this->any())
            ->method('getSourceContext')
            ->willReturn(new Source('', ''));

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('generateKey')
            ->willReturn('key');
        $cache->expects($this->once())
            ->method('write');

        $twig = new Environment($loader, ['cache' => $cache, 'auto_reload' => true, 'debug' => false]);
        $twig->generateTemplateCache($templateName);
    }

    public function testGenerateTemplateCacheCachingIsDisabled()
    {
        $templateName = __FUNCTION__;

        $loader = $this->getLoader();
        $loader->expects($this->never())
            ->method('getSourceContext')
            ->willReturn(new Source('', ''));

        $cache = $this->createMock(CacheInterface::class);
        // Caching is disabled: generateKey returns false
        $cache->expects($this->once())
            ->method('generateKey')
            ->willReturn('');
        $cache->expects($this->never())
            ->method('write');

        $twig = new Environment($loader, ['cache' => $cache, 'auto_reload' => true, 'debug' => false]);
        $twig->generateTemplateCache($templateName);
    }
}
