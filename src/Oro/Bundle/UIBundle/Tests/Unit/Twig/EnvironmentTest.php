<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Tests\Unit\Twig\Fixture\EnvironmentExtension;
use Oro\Bundle\UIBundle\Twig\Environment;
use Oro\Component\Testing\TempDirExtension;

/**
 * Copy of Twig_Tests_EnvironmentTest. Should be removed after merging of pull-request with this service changes.
 */
class EnvironmentTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /**
     * @expectedException        \LogicException
     * @expectedExceptionMessage You must set a loader first.
     */
    public function testRenderNoLoader()
    {
        $env = new Environment();
        $env->render('test');
    }

    public function testAutoescapeOption()
    {
        $loader = new \Twig_Loader_Array(
            array(
                'html' => '{{ foo }} {{ foo }}',
                'js'   => '{{ bar }} {{ bar }}',
            )
        );

        $twig = new Environment(
            $loader,
            array(
                'debug'      => true,
                'cache'      => false,
                'autoescape' => array($this, 'escapingStrategyCallback'),
            )
        );

        $this->assertEquals('foo&lt;br/ &gt; foo&lt;br/ &gt;', $twig->render('html', array('foo' => 'foo<br/ >')));
        $this->assertEquals(
            'foo\u003Cbr\/\u0020\u003E foo\u003Cbr\/\u0020\u003E',
            $twig->render('js', array('bar' => 'foo<br/ >'))
        );
    }

    public function escapingStrategyCallback($filename)
    {
        return $filename;
    }

    public function testGlobals()
    {
        // globals can be added after calling getGlobals
        $twig = new Environment(new \Twig_Loader_String());
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->addGlobal('foo', 'bar');
        $globals = $twig->getGlobals();
        $this->assertEquals('bar', $globals['foo']);

        // globals can be modified after runtime init
        $twig = new Environment(new \Twig_Loader_String());
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->initRuntime();
        $twig->addGlobal('foo', 'bar');
        $globals = $twig->getGlobals();
        $this->assertEquals('bar', $globals['foo']);

        // globals can be modified after extensions init
        $twig = new Environment(new \Twig_Loader_String());
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->getFunctions();
        $twig->addGlobal('foo', 'bar');
        $globals = $twig->getGlobals();
        $this->assertEquals('bar', $globals['foo']);

        // globals can be modified after extensions and runtime init
        $twig = new Environment(new \Twig_Loader_String());
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->getFunctions();
        $twig->initRuntime();
        $twig->addGlobal('foo', 'bar');
        $globals = $twig->getGlobals();
        $this->assertEquals('bar', $globals['foo']);

        $twig = new Environment(new \Twig_Loader_String());
        $twig->getGlobals();
        $twig->addGlobal('foo', 'bar');
        $template = $twig->loadTemplate('{{foo}}');
        $this->assertEquals('bar', $template->render(array()));
    }

    public function testExtensionsAreNotInitializedWhenRenderingACompiledTemplate()
    {
        $options = [
            'cache'       => $this->getTempDir('twig'),
            'auto_reload' => false,
            'debug'       => false
        ];

        // force compilation
        $twig = new Environment(new \Twig_Loader_String(), $options);
        $cache = $twig->getCacheFilename('{{ foo }}');
        if (!is_dir(dirname($cache))) {
            mkdir(dirname($cache), 0777, true);
        }
        file_put_contents($cache, $twig->compileSource('{{ foo }}', '{{ foo }}'));

        // check that extensions won't be initialized when rendering a template that is already in the cache
        $twig = $this
            ->getMockBuilder('Twig_Environment')
            ->setConstructorArgs(array(new \Twig_Loader_String(), $options))
            ->setMethods(array('initExtensions'))
            ->getMock()
        ;

        $twig->expects($this->never())->method('initExtensions');

        // render template
        $output = $twig->render('{{ foo }}', array('foo' => 'bar'));
        $this->assertEquals('bar', $output);

        unlink($cache);
    }

    public function testAddExtension()
    {
        $twig = new Environment(new \Twig_Loader_String());
        $twig->addExtension(new EnvironmentExtension());

        $this->assertArrayHasKey('test', $twig->getTags());
        $this->assertArrayHasKey('foo_filter', $twig->getFilters());
        $this->assertArrayHasKey('foo_function', $twig->getFunctions());
        $this->assertArrayHasKey('foo_test', $twig->getTests());
        $this->assertArrayHasKey('foo_unary', $twig->getUnaryOperators());
        $this->assertArrayHasKey('foo_binary', $twig->getBinaryOperators());
        $this->assertArrayHasKey('foo_global', $twig->getGlobals());
        $visitors = $twig->getNodeVisitors();
        $this->assertEquals(
            'Oro\Bundle\UIBundle\Tests\Unit\Twig\Fixture\EnvironmentNodeVisitor',
            get_class($visitors[2])
        );
    }

    public function testRemoveExtension()
    {
        $twig = new Environment(new \Twig_Loader_String());
        $twig->addExtension(new EnvironmentExtension());
        $twig->removeExtension('environment_test');

        $this->assertFalse(array_key_exists('test', $twig->getTags()));
        $this->assertFalse(array_key_exists('foo_filter', $twig->getFilters()));
        $this->assertFalse(array_key_exists('foo_function', $twig->getFunctions()));
        $this->assertFalse(array_key_exists('foo_test', $twig->getTests()));
        $this->assertFalse(array_key_exists('foo_unary', $twig->getUnaryOperators()));
        $this->assertFalse(array_key_exists('foo_binary', $twig->getBinaryOperators()));
        $this->assertFalse(array_key_exists('foo_global', $twig->getGlobals()));
        $this->assertCount(2, $twig->getNodeVisitors());
    }
}
