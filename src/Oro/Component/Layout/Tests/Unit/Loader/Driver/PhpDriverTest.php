<?php

namespace Oro\Component\Layout\Tests\Unit\Loader\Driver;

use Oro\Component\Layout\Exception\SyntaxException;
use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\Layout\Loader\Driver\PhpDriver;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Generator\LayoutUpdateGeneratorInterface;
use Oro\Component\Testing\TempDirExtension;

class PhpDriverTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    protected $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = $this->getTempDir('layouts', false);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function getPath($path)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    public function testEmptyCacheDirException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $generator = $this->createMock(LayoutUpdateGeneratorInterface::class);
        $this->getLoader($generator);
    }

    public function testLoadInDebugMode()
    {
        $generator = $this->createMock(LayoutUpdateGeneratorInterface::class);
        $loader = $this->getLoader($generator, true, $this->cacheDir);

        $generator->expects($this->once())->method('generate')->willReturnCallback([$this, 'buildClass']);

        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../Stubs/Updates/layout_update.php';
        $path = $this->getPath($path);

        $update = $loader->load($path);
        $this->assertInstanceOf(LayoutUpdateInterface::class, $update);
    }

    public function testLoadInProductionMode()
    {
        $generator = $this->createMock(LayoutUpdateGeneratorInterface::class);
        $loader = $this->getLoader($generator, false, $this->cacheDir);

        $generator->expects($this->once())->method('generate')->willReturnCallback([$this, 'buildClass']);

        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../Stubs/Updates/layout_update2.php';
        $path = $this->getPath($path);

        $update = $loader->load($path);
        $this->assertInstanceOf(LayoutUpdateInterface::class, $update);
        $this->assertCount(1, $files = iterator_to_array(new \FilesystemIterator($this->cacheDir)));
    }

    public function testProcessSyntaxExceptions()
    {
        $generator = $this->createMock(LayoutUpdateGeneratorInterface::class);
        $loader = $this->getLoader($generator, true, $this->cacheDir);

        $exception = new SyntaxException(
            'Some error found',
            "\$layoutManipulator->add('header', 'root', 'header');\n",
            0
        );
        $generator->expects($this->once())->method('generate')->willThrowException($exception);

        $message = <<<MESSAGE
Syntax error: Some error found at "0"
\$layoutManipulator->add('header', 'root', 'header');


Filename:
MESSAGE;
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($message);

        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../Stubs/Updates/layout_update4.php';
        $path = $this->getPath($path);

        $update = $loader->load($path);
        $this->assertInstanceOf(LayoutUpdateInterface::class, $update);
    }

    public function testGetUpdateFilenamePattern()
    {
        $loader = $this->getLoader(null, false, $this->cacheDir);
        $this->assertEquals('/^(?!.*html\.php$).*\.php$/', $loader->getUpdateFilenamePattern('php'));
    }

    /**
     * @param string        $className
     * @param GeneratorData $data
     *
     * @return string
     */
    public function buildClass($className, GeneratorData $data)
    {
        $data = str_replace(['<?php', '<?', '?>'], '', $data->getSource());

        return <<<CLASS
<?php
    use \Oro\Component\Layout\LayoutManipulatorInterface;
    use \Oro\Component\Layout\LayoutItemInterface;

    class $className implements \Oro\Component\Layout\LayoutUpdateInterface
    {
        public function updateLayout(LayoutManipulatorInterface \$layoutManipulator, LayoutItemInterface \$item)
        {
            $data
        }
    }
CLASS;
    }

    /**
     * @param null|LayoutUpdateGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject $generator
     * @param bool                                                                         $debug
     * @param bool                                                                         $cache
     *
     * @return PhpDriver
     */
    protected function getLoader($generator = null, $debug = false, $cache = false)
    {
        $generator = null === $generator
            ? $this->createMock(LayoutUpdateGeneratorInterface::class)
            : $generator;

        return new PhpDriver($generator, $debug, $cache);
    }
}
