<?php

namespace Oro\Component\Layout\Tests\Unit\Loader\Driver;

use Symfony\Component\Filesystem\Filesystem;

use Oro\Component\Layout\Exception\SyntaxException;
use Oro\Component\Layout\Loader\Driver\YamlDriver;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Generator\LayoutUpdateGeneratorInterface;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;

class YamlDriverTest extends \PHPUnit_Framework_TestCase
{
    protected $cacheDir;

    public function setUp()
    {
        parent::setUp();

        $this->cacheDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'layouts';

        $fs = new Filesystem();
        $fs->remove($this->cacheDir);
    }

    public function tearDown()
    {
        parent::tearDown();

        $fs = new Filesystem();
        $fs->remove($this->cacheDir);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testEmptyCacheDirException()
    {
        $generator = $this->getMock('Oro\Component\Layout\Loader\Generator\LayoutUpdateGeneratorInterface');
        $this->getLoader($generator);
    }

    public function testLoadInDebugMode()
    {
        $generator = $this->getMock('Oro\Component\Layout\Loader\Generator\LayoutUpdateGeneratorInterface');
        $loader    = $this->getLoader($generator, false, $this->cacheDir);

        $generator->expects($this->once())->method('generate')->willReturnCallback([$this, 'buildClass']);

        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../Stubs/Updates/layout_update.yml';
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        $update = $loader->load($path);
        $this->assertInstanceOf('Oro\Component\Layout\LayoutUpdateInterface', $update);
    }

    public function testLoadInProductionMode()
    {
        $generator = $this->getMock('Oro\Component\Layout\Loader\Generator\LayoutUpdateGeneratorInterface');
        $loader    = $this->getLoader($generator, false, $this->cacheDir);

        $generator->expects($this->once())->method('generate')->willReturnCallback([$this, 'buildClass']);

        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../Stubs/Updates/layout_update2.yml';
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        $update = $loader->load($path);
        $this->assertInstanceOf('Oro\Component\Layout\LayoutUpdateInterface', $update);
        $this->assertCount(1, $files = iterator_to_array(new \FilesystemIterator($this->cacheDir)));
    }

    public function testPassElementVisitor()
    {
        $generator = $this->getMock('Oro\Component\Layout\Loader\Generator\LayoutUpdateGeneratorInterface');
        $loader    = $this->getLoader($generator, false, $this->cacheDir);

        $path     = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../Stubs/Updates/_header.yml';
        $path     = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $resource = $path;

        $generator->expects($this->once())->method('generate')
            ->willReturnCallback(
                function ($className, $data, VisitorCollection $collection) use ($resource) {
                    $this->assertContainsOnlyInstancesOf(
                        'Oro\Component\Layout\Loader\Visitor\ElementDependentVisitor',
                        $collection
                    );

                    return $this->buildClass($className, $data);
                }
            );

        $loader->load($resource);
    }

    public function testPassesParsedYamlContentToGenerator()
    {
        $generator = $this->getMock('Oro\Component\Layout\Loader\Generator\LayoutUpdateGeneratorInterface');
        $loader    = $this->getLoader($generator, false, $this->cacheDir);

        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../Stubs/Updates/layout_update4.yml';
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        $generator->expects($this->once())->method('generate')
            ->willReturnCallback(
                function ($className, GeneratorData $data) use ($path) {
                    $this->assertNotEmpty($data);
                    $this->assertSame(
                        ['actions' => [['@add' => ['id' => 'root', 'parent' => null]]]],
                        $data->getSource()
                    );
                    $this->assertSame($path, $data->getFilename());

                    return $this->buildClass($className);
                }
            );

        $loader->load($path);
    }

    public function testProcessSyntaxExceptions()
    {
        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../Stubs/Updates/layout_update5.yml';
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        $generator = $this->getMock('Oro\Component\Layout\Loader\Generator\LayoutUpdateGeneratorInterface');
        $loader    = $this->getLoader($generator, false, $this->cacheDir);

        $exception = new SyntaxException(
            'action name should start with "@" symbol, current name "add"',
            ['add' => ['id' => 'myId', 'parentId' => 'myParentId']],
            'actions.0'
        );
        $generator->expects($this->once())->method('generate')->willThrowException($exception);

        $message = <<<MESSAGE
Syntax error: action name should start with "@" symbol, current name "add" at "actions.0"
add:
    id: myId
    parentId: myParentId


Filename: $path
MESSAGE;
        $this->setExpectedException('\RuntimeException', $message);

        $update = $loader->load($path);
        $this->assertInstanceOf('Oro\Component\Layout\LayoutUpdateInterface', $update);
    }

    /**
     * @param string $className
     *
     * @return string
     */
    public function buildClass($className)
    {
        return <<<CLASS
<?php
    use \Oro\Component\Layout\LayoutManipulatorInterface;
    use \Oro\Component\Layout\LayoutItemInterface;

    class $className implements \Oro\Component\Layout\LayoutUpdateInterface
    {
        public function updateLayout(LayoutManipulatorInterface \$layoutManipulator, LayoutItemInterface \$item)
        {
        }
    }
CLASS;
    }

    /**
     * @param null|LayoutUpdateGeneratorInterface $generator
     * @param bool                                $debug
     * @param bool                                $cache
     *
     * @return YamlDriver
     */
    protected function getLoader($generator = null, $debug = false, $cache = false)
    {
        $generator = null === $generator
            ? $this->getMock('Oro\Component\Layout\Loader\Generator\LayoutUpdateGeneratorInterface')
            : $generator;

        return new YamlDriver($generator, $debug, $cache);
    }
}
