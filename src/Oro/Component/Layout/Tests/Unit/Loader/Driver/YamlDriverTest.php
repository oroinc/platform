<?php

namespace Oro\Component\Layout\Tests\Unit\Loader\Driver;

use Oro\Component\Layout\Exception\SyntaxException;
use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\Layout\Loader\Driver\YamlDriver;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Generator\LayoutUpdateGeneratorInterface;
use Oro\Component\Layout\Loader\Visitor\ElementDependentVisitor;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use Oro\Component\Testing\TempDirExtension;

class YamlDriverTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = $this->getTempDir('layouts', false);
    }

    private function getPath(string $path): string
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
        $loader = $this->getLoader($generator, false, $this->cacheDir);

        $generator->expects($this->once())
            ->method('generate')
            ->willReturnCallback([$this, 'buildClass']);

        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../Stubs/Updates/layout_update.yml';
        $path = $this->getPath($path);

        $update = $loader->load($path);
        $this->assertInstanceOf(LayoutUpdateInterface::class, $update);
    }

    public function testLoadInProductionMode()
    {
        $generator = $this->createMock(LayoutUpdateGeneratorInterface::class);
        $loader = $this->getLoader($generator, false, $this->cacheDir);

        $generator->expects($this->once())
            ->method('generate')
            ->willReturnCallback([$this, 'buildClass']);

        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../Stubs/Updates/layout_update2.yml';
        $path = $this->getPath($path);

        $update = $loader->load($path);
        $this->assertInstanceOf(LayoutUpdateInterface::class, $update);
        $this->assertCount(1, $files = iterator_to_array(new \FilesystemIterator($this->cacheDir)));
    }

    public function testPassElementVisitor()
    {
        $generator = $this->createMock(LayoutUpdateGeneratorInterface::class);
        $loader = $this->getLoader($generator, false, $this->cacheDir);

        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../Stubs/Updates/_header.yml';
        $path = $this->getPath($path);
        $resource = $path;

        $generator->expects($this->once())
            ->method('generate')
            ->willReturnCallback(function ($className, $data, VisitorCollection $collection) {
                $this->assertContainsOnlyInstancesOf(
                    ElementDependentVisitor::class,
                    $collection
                );

                return $this->buildClass($className, $data);
            });

        $loader->load($resource);
    }

    public function testPassesParsedYamlContentToGenerator()
    {
        $generator = $this->createMock(LayoutUpdateGeneratorInterface::class);
        $loader = $this->getLoader($generator, false, $this->cacheDir);

        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../Stubs/Updates/layout_update4.yml';
        $path = $this->getPath($path);

        $generator->expects($this->once())
            ->method('generate')
            ->willReturnCallback(function ($className, GeneratorData $data) use ($path) {
                $this->assertNotEmpty($data);
                $this->assertSame(
                    ['actions' => [['@add' => ['id' => 'root', 'parent' => null]]]],
                    $data->getSource()
                );
                $this->assertSame($path, $data->getFilename());

                return $this->buildClass($className);
            });

        $loader->load($path);
    }

    public function testProcessSyntaxExceptions()
    {
        $path = rtrim(__DIR__, DIRECTORY_SEPARATOR) . '/../Stubs/Updates/layout_update5.yml';
        $path = $this->getPath($path);

        $generator = $this->createMock(LayoutUpdateGeneratorInterface::class);
        $loader = $this->getLoader($generator, false, $this->cacheDir);

        $exception = new SyntaxException(
            'action name should start with "@" symbol, current name "add"',
            ['add' => ['id' => 'myId', 'parentId' => 'myParentId']],
            'actions.0'
        );
        $generator->expects($this->once())
            ->method('generate')
            ->willThrowException($exception);

        $message = <<<MESSAGE
Syntax error: action name should start with "@" symbol, current name "add" at "actions.0"
add:
    id: myId
    parentId: myParentId


Filename: $path
MESSAGE;
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($message);

        $update = $loader->load($path);
        $this->assertInstanceOf(LayoutUpdateInterface::class, $update);
    }

    public function testGetUpdateFilenamePattern()
    {
        $loader = $this->getLoader(null, false, $this->cacheDir);
        $this->assertEquals('/\.yml$/', $loader->getUpdateFilenamePattern('yml'));
    }

    public function buildClass(string $className): string
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

    private function getLoader(
        ?LayoutUpdateGeneratorInterface $generator,
        bool $debug = false,
        string $cacheDir = ''
    ): YamlDriver {
        return new YamlDriver(
            $generator ?? $this->createMock(LayoutUpdateGeneratorInterface::class),
            $debug,
            $cacheDir
        );
    }
}
